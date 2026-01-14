#!/bin/bash

# B2B Data Backup Script
# Handles backup of B2B specific data for business development module

set -e

# Configuration
BACKUP_DIR="/var/backups/b2b"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="b2b_backup_${TIMESTAMP}"
LOG_FILE="/var/log/b2b-backup.log"

# Database credentials (should be sourced from secure location)
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-backup_user}"
DB_PASS="${DB_PASS:-secure_password}"
DB_NAME="${DB_NAME:-sibali_b2b}"

# AWS S3 configuration (if using S3)
S3_BUCKET="${S3_BUCKET:-sibali-backups}"
S3_PREFIX="b2b/"

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

log "Starting B2B data backup: $BACKUP_NAME"

# Backup database tables related to B2B
B2B_TABLES="business_development_contracts corporate_accounts partnerships sales_pipeline b2b_leads"

for table in $B2B_TABLES; do
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW TABLES LIKE '$table'" "$DB_NAME" | grep -q "$table"; then
        log "Backing up table: $table"
        mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" "$table" > "$BACKUP_DIR/${table}_${TIMESTAMP}.sql"
        gzip "$BACKUP_DIR/${table}_${TIMESTAMP}.sql"
    else
        log "Table $table not found, skipping"
    fi
done

# Backup B2B related files (contracts, proposals, etc.)
B2B_FILES_DIR="/var/www/sibali/storage/app/b2b"
if [ -d "$B2B_FILES_DIR" ]; then
    log "Backing up B2B files"
    tar -czf "$BACKUP_DIR/b2b_files_${TIMESTAMP}.tar.gz" -C "$B2B_FILES_DIR" .
fi

# Create archive of all backups
log "Creating consolidated backup archive"
tar -czf "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" -C "$BACKUP_DIR" . --exclude="${BACKUP_NAME}.tar.gz"

# Upload to S3 if configured
if command -v aws &> /dev/null && [ -n "$S3_BUCKET" ]; then
    log "Uploading backup to S3"
    aws s3 cp "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" "s3://$S3_BUCKET/$S3_PREFIX" --storage-class STANDARD_IA
fi

# Cleanup old backups (keep last 30 days)
log "Cleaning up old backups"
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete

# Verify backup integrity
if [ -f "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" ]; then
    log "Backup completed successfully: $BACKUP_DIR/${BACKUP_NAME}.tar.gz"
    # Send notification (implement based on your notification system)
    # curl -X POST -H 'Content-type: application/json' --data '{"text":"B2B Backup completed successfully"}' YOUR_SLACK_WEBHOOK
else
    log "ERROR: Backup failed!"
    exit 1
fi

log "B2B backup process completed"
