#!/bin/bash

# Restore Script for Sibali.id
# Purpose: Helper restore non-emergency untuk dev/QA atau partial restores
# Function: Restore from backup with validation and safeguards

set -e

# Configuration
BACKUP_ROOT="/var/backups/sibali"
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-restore_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"
RESTORE_ENV="${RESTORE_ENV:-development}"
FORCE_RESTORE="${FORCE_RESTORE:-false}"
LOG_FILE="${BACKUP_ROOT}/logs/restore.log"

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Safety checks
if [ "$RESTORE_ENV" = "production" ] && [ "$FORCE_RESTORE" != "true" ]; then
    log "ERROR: Production restore requires FORCE_RESTORE=true"
    exit 1
fi

# Get backup to restore
if [ $# -eq 0 ]; then
    echo "Usage: $0 <backup_directory_or_latest>"
    echo "Available backups:"
    find "$BACKUP_ROOT" -name "full_*" -o -name "incremental_*" | sort
    exit 1
fi

BACKUP_DIR="$1"
if [ "$BACKUP_DIR" = "latest" ]; then
    BACKUP_DIR=$(readlink -f "${BACKUP_ROOT}/latest_incremental" 2>/dev/null || readlink -f "${BACKUP_ROOT}/latest_full" 2>/dev/null)
fi

if [ ! -d "$BACKUP_DIR" ]; then
    log "ERROR: Backup directory $BACKUP_DIR not found"
    exit 1
fi

log "Starting restore from: $BACKUP_DIR"

# Validate backup manifest
if [ ! -f "$BACKUP_DIR/manifest.txt" ]; then
    log "ERROR: Backup manifest not found"
    exit 1
fi

# Read manifest
BACKUP_TYPE=$(grep "Backup Type:" "$BACKUP_DIR/manifest.txt" | cut -d: -f2 | xargs)
TIMESTAMP=$(grep "Timestamp:" "$BACKUP_DIR/manifest.txt" | cut -d: -f2 | xargs)

log "Backup Type: $BACKUP_TYPE, Timestamp: $TIMESTAMP"

# Validate checksums if available
if [ -f "$BACKUP_DIR/checksums.sha256" ]; then
    log "Validating checksums..."
    if ! (cd "$BACKUP_DIR" && sha256sum -c checksums.sha256 >/dev/null 2>&1); then
        log "ERROR: Checksum validation failed"
        exit 1
    fi
    log "Checksums validated successfully"
fi

# Database restore
if [ -d "$BACKUP_DIR/mysql" ] || [ -f "$BACKUP_DIR/database.sql.gz" ]; then
    log "Restoring database..."

    # Create temporary database for restore
    TEMP_DB="${DB_NAME}_restore_$(date +%s)"

    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE $TEMP_DB;"

    if [ -f "$BACKUP_DIR/database.sql.gz" ]; then
        gunzip -c "$BACKUP_DIR/database.sql.gz" | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$TEMP_DB"
    elif [ -d "$BACKUP_DIR/mysql" ]; then
        # Physical restore (more complex, simplified here)
        log "Physical restore not implemented in this script"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE $TEMP_DB;"
        exit 1
    fi

    # Run post-restore migrations if needed
    if [ -f "$BACKUP_DIR/migrations_to_run.txt" ]; then
        log "Running post-restore migrations..."
        while read -r migration; do
            php artisan migrate --path="$migration" --database="$TEMP_DB" || true
        done < "$BACKUP_DIR/migrations_to_run.txt"
    fi

    # Sanity checks
    TABLE_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $TEMP_DB; SHOW TABLES;" | wc -l)
    log "Restored database has $TABLE_COUNT tables"

    if [ "$TABLE_COUNT" -lt 10 ]; then
        log "WARNING: Low table count detected, restore may be incomplete"
    fi

    # Switch databases if in development
    if [ "$RESTORE_ENV" = "development" ]; then
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE $DB_NAME; RENAME DATABASE $TEMP_DB TO $DB_NAME;"
        log "Database restored to $DB_NAME"
    else
        log "Database restored to temporary: $TEMP_DB"
        echo "To complete restore, manually rename $TEMP_DB to $DB_NAME"
    fi
fi

# File restore
if [ -d "$BACKUP_DIR/files" ]; then
    log "Restoring files..."

    TARGET_DIR="${RESTORE_FILES_DIR:-/var/www/html/storage/app}"
    mkdir -p "$TARGET_DIR"

    # Restore with rsync
    rsync -a --delete "$BACKUP_DIR/files/" "$TARGET_DIR/"

    # Fix permissions
    chown -R www-data:www-data "$TARGET_DIR" 2>/dev/null || true

    log "Files restored to $TARGET_DIR"
fi

# Post-restore tasks
log "Running post-restore tasks..."

# Clear caches
php artisan cache:clear || true
php artisan config:clear || true
php artisan view:clear || true

# Reindex if needed
php artisan search:reindex || true

log "Restore completed successfully from $BACKUP_DIR"

# Create restore report
cat > "${BACKUP_DIR}/restore_report.txt" << EOF
Restore Report
==============
Backup: $BACKUP_DIR
Type: $BACKUP_TYPE
Timestamp: $TIMESTAMP
Restored At: $(date)
Environment: $RESTORE_ENV
Database: ${TEMP_DB:-$DB_NAME}
Files: ${TARGET_DIR:-N/A}
Status: SUCCESS
EOF

exit 0
