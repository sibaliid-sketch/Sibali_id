#!/bin/bash

# Cache Clearing Utility for Sibali.id
# Invalidates Redis cache, purges CDN, and resets PHP opcache

set -e

# Configuration
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
REDIS_PORT="${REDIS_PORT:-6379}"
REDIS_DB="${REDIS_DB:-0}"
REDIS_PASSWORD="${REDIS_PASSWORD:-}"

# CDN Configuration (adjust based on your provider)
CDN_PURGE_URL="${CDN_PURGE_URL:-}"
CDN_API_KEY="${CDN_API_KEY:-}"

# PHP Configuration
PHP_FPM_SOCKET="${PHP_FPM_SOCKET:-/var/run/php/php8.1-fpm.sock}"

# Logging
LOG_FILE="./storage/logs/cache_clear_$(date +"%Y%m%d_%H%M%S").log"

# Dry run flag
DRY_RUN=false
CONFIRMED=false

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Show usage
usage() {
    echo "Usage: $0 [OPTIONS] [PATTERN]"
    echo "Clear cache for Sibali.id"
    echo ""
    echo "OPTIONS:"
    echo "  -d, --dry-run          Show what would be cleared without doing it"
    echo "  -y, --yes              Skip confirmation prompts"
    echo "  -h, --help             Show this help"
    echo ""
    echo "PATTERN: Redis key pattern to clear (default: all cache keys)"
    echo ""
    echo "Examples:"
    echo "  $0 --dry-run           # Preview all cache clearing"
    echo "  $0 'laravel:*'         # Clear Laravel cache keys"
    echo "  $0 'user:*'            # Clear user-related cache"
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -d|--dry-run)
            DRY_RUN=true
            shift
            ;;
        -y|--yes)
            CONFIRMED=true
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            PATTERN="$1"
            shift
            ;;
    esac
done

# Redis connection string
REDIS_CMD="redis-cli -h $REDIS_HOST -p $REDIS_PORT -n $REDIS_DB"
if [ -n "$REDIS_PASSWORD" ]; then
    REDIS_CMD="$REDIS_CMD -a $REDIS_PASSWORD"
fi

# Test Redis connection
test_redis_connection() {
    if ! $REDIS_CMD ping &>/dev/null; then
        log "ERROR: Cannot connect to Redis at $REDIS_HOST:$REDIS_PORT"
        return 1
    fi
    return 0
}

# Clear Redis keys by pattern
clear_redis_keys() {
    local pattern="${1:-*}"
    local count=0

    log "Clearing Redis keys matching pattern: $pattern"

    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would clear the following keys:"
        $REDIS_CMD --scan --pattern "$pattern" | head -20 | sed 's/^/  /'
        echo "  ... (showing first 20 keys)"
        return
    fi

    # Get keys matching pattern
    local keys
    mapfile -t keys < <($REDIS_CMD --scan --pattern "$pattern")

    if [ ${#keys[@]} -eq 0 ]; then
        log "No keys found matching pattern: $pattern"
        return
    fi

    # Clear keys in batches to avoid blocking
    local batch_size=100
    for ((i = 0; i < ${#keys[@]}; i += batch_size)); do
        local batch_keys=("${keys[@]:i:batch_size}")
        if [ ${#batch_keys[@]} -gt 0 ]; then
            $REDIS_CMD del "${batch_keys[@]}" >/dev/null
            ((count += ${#batch_keys[@]}))
            log "Cleared batch of ${#batch_keys[@]} keys"
        fi
    done

    log "Cleared $count Redis keys"
}

# Purge CDN cache
purge_cdn() {
    if [ -z "$CDN_PURGE_URL" ]; then
        log "CDN purge URL not configured, skipping"
        return
    fi

    log "Purging CDN cache..."

    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would purge CDN at $CDN_PURGE_URL"
        return
    fi

    # Rate limiting check (simple file-based)
    local rate_limit_file="/tmp/cdn_purge_last"
    if [ -f "$rate_limit_file" ]; then
        local last_purge=$(cat "$rate_limit_file")
        local now=$(date +%s)
        local diff=$((now - last_purge))
        if [ $diff -lt 300 ]; then  # 5 minutes
            log "CDN purge rate limited (last purge $diff seconds ago)"
            return
        fi
    fi

    # Perform purge (adjust based on your CDN provider)
    if curl -s -X POST "$CDN_PURGE_URL" \
        -H "Authorization: Bearer $CDN_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"purge_everything": true}' >/dev/null; then
        log "CDN purge successful"
        echo $(date +%s) > "$rate_limit_file"
    else
        log "ERROR: CDN purge failed"
    fi
}

# Reset PHP opcache
reset_php_opcache() {
    log "Resetting PHP opcache..."

    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would reset PHP opcache"
        return
    fi

    # Try different methods to reset opcache
    if [ -S "$PHP_FPM_SOCKET" ]; then
        # Via FPM socket
        echo -e "cache:clear\nquit" | socat unix-connect:"$PHP_FPM_SOCKET" - >/dev/null 2>&1 || true
        log "Attempted opcache reset via FPM socket"
    fi

    # Via web endpoint (if available)
    if [ -n "$APP_URL" ]; then
        curl -s "$APP_URL/opcache-reset" >/dev/null 2>&1 || true
        log "Attempted opcache reset via web endpoint"
    fi

    # Restart FPM if needed (use with caution)
    if [ "$CONFIRMED" = true ] && command -v systemctl &>/dev/null; then
        log "Restarting PHP-FPM service..."
        sudo systemctl restart php8.1-fpm || true
    fi
}

# Clear Laravel specific caches
clear_laravel_cache() {
    log "Clearing Laravel caches..."

    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would clear Laravel caches"
        return
    fi

    # Use artisan commands if available
    if [ -f "artisan" ]; then
        php artisan cache:clear >/dev/null 2>&1 || log "Failed to clear Laravel cache"
        php artisan config:clear >/dev/null 2>&1 || log "Failed to clear Laravel config cache"
        php artisan route:clear >/dev/null 2>&1 || log "Failed to clear Laravel route cache"
        php artisan view:clear >/dev/null 2>&1 || log "Failed to clear Laravel view cache"
        log "Laravel caches cleared"
    else
        log "Artisan not found, skipping Laravel cache clearing"
    fi
}

# Confirmation prompt
confirm_action() {
    if [ "$CONFIRMED" = true ] || [ "$DRY_RUN" = true ]; then
        return 0
    fi

    echo "This will clear caches. Are you sure? (y/N)"
    read -r response
    case $response in
        [Yy]|[Yy][Ee][Ss])
            return 0
            ;;
        *)
            log "Operation cancelled by user"
            exit 1
            ;;
    esac
}

# Main execution
log "Starting cache clearing operation"
log "Dry run: $DRY_RUN"

# Test connections
if ! test_redis_connection; then
    log "Redis connection failed, continuing with other operations..."
fi

# Confirm action
confirm_action

# Perform cache clearing
clear_redis_keys "$PATTERN"
clear_laravel_cache
reset_php_opcache
purge_cdn

log "Cache clearing operation completed"
log "Log file: $LOG_FILE"
