#!/bin/bash
# inode-cleaner.sh for shared hosting (hPanel/Hostinger)
# Cron-safe, idempotent cleaning for Laravel sibali.id
# Replace {USERNAME} with actual username before running

USERNAME="{USERNAME}"
PROJECT_ROOT="/home/$USERNAME/domains/sibali.id/public_html"
LOG_DIR="/home/$USERNAME/logs"
DATE=$(date +%Y%m%d)

# Configuration
KEEP_LOG_DAYS=30
SESSION_AGE_DAYS=7
TMP_PATHS=("storage/tmp" "storage/backups")
EXCLUDE_PATHS=("vendor" "public/assets" "storage/app/uploads")

# Log file for cleanup results
CLEANUP_LOG="$LOG_DIR/cleanup-$DATE.log"

cd "$PROJECT_ROOT" || exit 1

# Function to log actions
log_action() {
    echo "$(date): $1" >> "$CLEANUP_LOG"
}

log_action "Starting cleanup"

# Clear Laravel caches (safe, regenerates on demand)
php artisan cache:clear 2>> "$CLEANUP_LOG"
php artisan view:clear 2>> "$CLEANUP_LOG"
php artisan config:clear 2>> "$CLEANUP_LOG"

# Remove old logs
find storage/logs -name "*.log" -mtime +$KEEP_LOG_DAYS -delete 2>> "$CLEANUP_LOG"

# Remove old sessions
find storage/framework/sessions -name "*" -mtime +$SESSION_AGE_DAYS -delete 2>> "$CLEANUP_LOG"

# Clear bootstrap cache (safe, regenerates)
rm -f bootstrap/cache/*.php 2>> "$CLEANUP_LOG"

# Clean tmp/backup paths, excluding important dirs
for path in "${TMP_PATHS[@]}"; do
    if [[ ! " ${EXCLUDE_PATHS[@]} " =~ " ${path} " ]]; then
        find "$path" -type f -mtime +30 -delete 2>> "$CLEANUP_LOG"
    fi
done

# Limit log size (truncate if >100MB)
if [ -f storage/logs/laravel.log ] && [ $(stat -c%s storage/logs/laravel.log 2>/dev/null || echo 0) -gt 104857600 ]; then
    echo "" > storage/logs/laravel.log
    log_action "Truncated laravel.log due to size"
fi

log_action "Cleanup completed"

# Rotate cleanup logs (keep last 10)
ls -t "$LOG_DIR"/cleanup-*.log 2>/dev/null | tail -n +11 | xargs rm -f 2>> "$CLEANUP_LOG"
