#!/bin/bash

# Cache Clearing Script for Sibali.id
# Purpose: Helper invalidasi cache: Redis, CDN purge, PHP opcache reset
# Function: Clear various cache layers safely

set -e

# Configuration
REDIS_HOST="${REDIS_HOST:-localhost}"
REDIS_PORT="${REDIS_PORT:-6379}"
REDIS_DB="${REDIS_DB:-0}"
REDIS_PASSWORD="${REDIS_PASSWORD:-}"
CDN_PURGE_URL="${CDN_PURGE_URL:-}"
DRY_RUN="${DRY_RUN:-false}"
LOG_FILE="/var/log/sibali/cache_clear.log"
CONFIRM="${CONFIRM:-false}"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Confirmation prompt
confirm_action() {
    local action="$1"
    if [ "$CONFIRM" = "true" ]; then
        read -p "Are you sure you want to $action? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Operation cancelled by user"
            exit 0
        fi
    fi
}

# Redis cache clearing
clear_redis_cache() {
    log "Clearing Redis cache..."

    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would clear Redis cache"
        return
    fi

    # Connect to Redis
    local redis_cmd="redis-cli"
    if [ -n "$REDIS_PASSWORD" ]; then
        redis_cmd="$redis_cmd -a $REDIS_PASSWORD"
    fi
    redis_cmd="$redis_cmd -h $REDIS_HOST -p $REDIS_PORT -n $REDIS_DB"

    # Get cache keys count before clearing
    local keys_before=$($redis_cmd DBSIZE 2>/dev/null || echo "0")
    log "Redis keys before clearing: $keys_before"

    # Clear cache keys (avoid clearing session keys)
    # Pattern: cache:* or laravel:*
    local cache_keys=$($redis_cmd KEYS "cache:*" 2>/dev/null | wc -l)
    local laravel_keys=$($redis_cmd KEYS "laravel:*" 2>/dev/null | wc -l)

    log "Found $cache_keys cache keys and $laravel_keys Laravel keys"

    # Delete cache keys
    $redis_cmd DEL $($redis_cmd KEYS "cache:*") >/dev/null 2>&1 || true
    $redis_cmd DEL $($redis_cmd KEYS "laravel:cache:*") >/dev/null 2>&1 || true

    local keys_after=$($redis_cmd DBSIZE 2>/dev/null || echo "0")
    log "Redis keys after clearing: $keys_after"
}

# Laravel application cache clearing
clear_laravel_cache() {
    log "Clearing Laravel application caches..."

    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would clear Laravel caches"
        return
    fi

    cd /var/www/html

    # Clear various Laravel caches
    php artisan cache:clear || log "WARNING: cache:clear failed"
    php artisan config:clear || log "WARNING: config:clear failed"
    php artisan route:clear || log "WARNING: route:clear failed"
    php artisan view:clear || log "WARNING: view:clear failed"
    php artisan event:clear || log "WARNING: event:clear failed"

    # Clear compiled classes
    php artisan clear-compiled || log "WARNING: clear-compiled failed"

    log "Laravel caches cleared"
}

# PHP OPcache reset
reset_php_opcache() {
    log "Resetting PHP OPcache..."

    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would reset PHP OPcache"
        return
    fi

    # Try to reset OPcache via FPM
    if command -v curl >/dev/null 2>&1; then
        # Use opcache_reset.php if available
        if [ -f "/var/www/html/public/opcache_reset.php" ]; then
            curl -s "http://localhost/opcache_reset.php" >/dev/null 2>&1 || true
            log "OPcache reset via HTTP"
        fi
    fi

    # Alternative: restart PHP-FPM
    if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        confirm_action "restart PHP-FPM"
        systemctl restart php8.2-fpm
        log "PHP-FPM restarted"
    elif systemctl is-active --quiet php-fpm 2>/dev/null; then
        confirm_action "restart PHP-FPM"
        systemctl restart php-fpm
        log "PHP-FPM restarted"
    else
        log "WARNING: Could not restart PHP-FPM"
    fi
}

# CDN cache purge
purge_cdn_cache() {
    if [ -z "$CDN_PURGE_URL" ]; then
        log "CDN purge URL not configured, skipping"
        return
    fi

    log "Purging CDN cache..."

    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would purge CDN cache at $CDN_PURGE_URL"
        return
    fi

    # Rate limit CDN purges to prevent abuse
    local last_purge_file="/tmp/last_cdn_purge"
    if [ -f "$last_purge_file" ]; then
        local last_purge=$(cat "$last_purge_file")
        local now=$(date +%s)
        local time_diff=$((now - last_purge))

        if [ $time_diff -lt 300 ]; then  # 5 minutes
            log "WARNING: CDN purge rate limited (last purge $time_diff seconds ago)"
            return
        fi
    fi

    # Purge CDN cache
    if curl -X POST "$CDN_PURGE_URL" \
            -H "Content-Type: application/json" \
            -d '{"purge_all": true}' \
            --max-time 30 \
            >/dev/null 2>&1; then
        log "CDN cache purged successfully"
        echo $(date +%s) > "$last_purge_file"
    else
        log "WARNING: CDN purge failed"
    fi
}

# Clear file-based caches
clear_file_caches() {
    log "Clearing file-based caches..."

    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would clear file caches"
        return
    fi

    # Clear storage/framework/cache
    if [ -d "/var/www/html/storage/framework/cache" ]; then
        find /var/www/html/storage/framework/cache -type f -delete 2>/dev/null || true
        log "File cache cleared"
    fi

    # Clear other temp directories
    find /tmp -name "laravel_*" -type f -mtime +1 -delete 2>/dev/null || true
}

# Main execution
log "Starting cache clearing operation (DRY_RUN=$DRY_RUN)"

# Execute cache clearing operations
clear_redis_cache
clear_laravel_cache
reset_php_opcache
purge_cdn_cache
clear_file_caches

log "Cache clearing completed"

# Optional: Warm up critical caches
if [ "${WARM_CACHE:-false}" = "true" ] && [ "$DRY_RUN" = "false" ]; then
    log "Warming up critical caches..."
    cd /var/www/html

    # Warm config and routes
    php artisan config:cache || true
    php artisan route:cache || true

    # Warm application cache with critical pages
    if command -v curl >/dev/null 2>&1; then
        curl -s "http://localhost/" >/dev/null 2>&1 || true
        curl -s "http://localhost/login" >/dev/null 2>&1 || true
    fi

    log "Cache warming completed"
fi

exit 0
