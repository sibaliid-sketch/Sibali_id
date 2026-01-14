#!/bin/bash

# Restore Script for Sibali.id
# Restores from backups with verification

set -e

# Configuration
BACKUP_ROOT="/var/backups/sibali"
LOG_FILE="/var/log/sibali-restore.log"
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

# Database configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"

# Encryption (if used)
ENCRYPT_BACKUP="${ENCRYPT_BACKUP:-false}"
ENCRYPT_KEY="${ENCRYPT_KEY:-/etc/sibali/backup.key}"

# Logging function
log() {
    echo "$TIMESTAMP - $1" | tee -a "$LOG_FILE"
}

# Function to show usage
usage() {
    echo "Usage: $0 <backup_file> [--dry-run] [--skip-files] [--skip-db]"
    echo "  backup_file: Path to backup file (.tar.gz or .tar.gz.enc)"
    echo "  --dry-run: Show what would be restored without actually doing it"
    echo "  --skip-files: Skip file restoration, only restore database"
    echo "  --skip-db: Skip database restoration, only restore files"
    exit 1
}

# Parse arguments
BACKUP_FILE=""
DRY_RUN=false
SKIP_FILES=false
SKIP_DB=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-files)
            SKIP_FILES=true
            shift
            ;;
        --skip-db)
            SKIP_DB=true
            shift
            ;;
        -*)
            echo "Unknown option: $1"
            usage
            ;;
        *)
            if [ -z "$BACKUP_FILE" ]; then
                BACKUP_FILE="$1"
            else
                echo "Multiple backup files specified"
                usage
            fi
            shift
            ;;
    esac
done

if [ -z "$BACKUP_FILE" ]; then
    echo "Backup file not specified"
    usage
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file does not exist: $BACKUP_FILE"
    exit 1
fi

log "Starting restore from: $BACKUP_FILE"

# Create temporary directory for extraction
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

# Decrypt if needed
if [[ "$BACKUP_FILE" == *.enc ]]; then
    if [ "$ENCRYPT_BACKUP" != "true" ] || [ ! -f "$ENCRYPT_KEY" ]; then
        log "ERROR: Encrypted backup but encryption not configured"
        exit 1
    fi
    log "Decrypting backup"
    DECRYPTED_FILE="$TEMP_DIR/decrypted_backup.tar.gz"
    openssl enc -d -aes-256-cbc -in "$BACKUP_FILE" -out "$DECRYPTED_FILE" -kfile "$ENCRYPT_KEY"
    BACKUP_FILE="$DECRYPTED_FILE"
fi

# Verify backup integrity
log "Verifying backup integrity"
if ! tar -tzf "$BACKUP_FILE" > /dev/null; then
    log "ERROR: Backup file is corrupted"
    exit 1
fi

# Extract backup
log "Extracting backup to temporary directory"
tar -xzf "$BACKUP_FILE" -C "$TEMP_DIR"

# Find database backup
DB_BACKUP=$(find "$TEMP_DIR" -name "database_*.sql.gz" | head -1)
if [ -z "$DB_BACKUP" ]; then
    log "ERROR: Database backup not found in archive"
    exit 1
fi

# Restore database if not skipped
if [ "$SKIP_DB" != "true" ]; then
    log "Restoring database"
    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would restore database from $DB_BACKUP"
    else
        # Create database backup before restore
        PRE_RESTORE_BACKUP="$BACKUP_ROOT/pre_restore_$(date +"%Y%m%d_%H%M%S").sql.gz"
        log "Creating pre-restore backup: $PRE_RESTORE_BACKUP"
        mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$PRE_RESTORE_BACKUP"

        # Restore database
        gunzip -c "$DB_BACKUP" | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"
        log "Database restored successfully"
    fi
else
    log "Skipping database restoration"
fi

# Restore files if not skipped
if [ "$SKIP_FILES" != "true" ]; then
    log "Restoring files"
    if [ "$DRY_RUN" = "true" ]; then
        log "DRY RUN: Would restore files from backup"
        find "$TEMP_DIR" -name "*.tar.gz" ! -name "database_*.sql.gz" -exec basename {} \;
    else
        # Restore each file archive
        for file_archive in "$TEMP_DIR"/*.tar.gz; do
            if [[ "$file_archive" != "$DB_BACKUP" ]]; then
                archive_name=$(basename "$file_archive" .tar.gz)
                log "Restoring archive: $archive_name"

                case $archive_name in
                    config_*)
                        tar -xzf "$file_archive" -C /var/www/sibali
                        ;;
                    storage_app_*)
                        mkdir -p /var/www/sibali/storage/app
                        tar -xzf "$file_archive" -C /var/www/sibali/storage/app
                        ;;
                    storage_logs_*)
                        mkdir -p /var/www/sibali/storage/logs
                        tar -xzf "$file_archive" -C /var/www/sibali/storage/logs
                        ;;
                    public_uploads_*)
                        mkdir -p /var/www/sibali/public/uploads
                        tar -xzf "$file_archive" -C /var/www/sibali/public/uploads
                        ;;
                    *)
                        log "Unknown archive type: $archive_name, skipping"
                        ;;
                esac
            fi
        done

        # Fix permissions
        chown -R www-data:www-data /var/www/sibali/storage
        chown -R www-data:www-data /var/www/sibali/bootstrap/cache
        chmod -R 755 /var/www/sibali/storage
        chmod -R 755 /var/www/sibali/bootstrap/cache

        log "Files restored successfully"
    fi
else
    log "Skipping file restoration"
fi

# Clear caches after restore
if [ "$DRY_RUN" != "true" ]; then
    log "Clearing application caches"
    cd /var/www/sibali
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

log "Restore completed successfully"

# Optional: Send notification
# curl -X POST -H 'Content-type: application/json' --data '{"text":"Restore completed successfully"}' YOUR_SLACK_WEBHOOK
