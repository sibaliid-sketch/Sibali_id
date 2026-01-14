#!/bin/bash

# General Backup Script for Sibali.id
# Comprehensive backup of database and files with encryption and rotation

set -e

# Configuration
BACKUP_ROOT="/var/backups/sibali"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="sibali_backup_${TIMESTAMP}"
LOG_FILE="/var/log/sibali-backup.log"

# Database configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-backup_user}"
DB_PASS="${DB_PASS}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"

# Encryption (optional)
ENCRYPT_BACKUP="${ENCRYPT_BACKUP:-false}"
ENCRYPT_KEY="${ENCRYPT_KEY:-/etc/sibali/backup.key}"

# Storage configuration
S3_BUCKET="${S3_BUCKET:-sibali-backups}"
S3_PREFIX="full-backups/"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

# Directories to backup
WEB_ROOT="/var/www/sibali"
BACKUP_DIRS=(
    "$WEB_ROOT/storage/app"
    "$WEB_ROOT/storage/logs"
    "$WEB_ROOT/public/uploads"
)

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Create backup directory
mkdir -p "$BACKUP_ROOT"

log "Starting full backup: $BACKUP_NAME"

# Database backup
log "Backing up database"
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" > "$BACKUP_ROOT/database_${TIMESTAMP}.sql"

gzip "$BACKUP_ROOT/database_${TIMESTAMP}.sql"

# Files backup
log "Backing up files"
for dir in "${BACKUP_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        dirname=$(basename "$dir")
        log "Backing up directory: $dirname"
        tar -czf "$BACKUP_ROOT/${dirname}_${TIMESTAMP}.tar.gz" -C "$(dirname "$dir")" "$dirname"
    fi
done

# Backup configuration files (excluding sensitive data)
log "Backing up configuration"
tar -czf "$BACKUP_ROOT/config_${TIMESTAMP}.tar.gz" \
    --exclude="*.env" \
    --exclude="*password*" \
    --exclude="*secret*" \
    -C "$WEB_ROOT" config/

# Create consolidated backup
log "Creating consolidated backup archive"
tar -czf "$BACKUP_ROOT/${BACKUP_NAME}.tar.gz" \
    -C "$BACKUP_ROOT" \
    database_${TIMESTAMP}.sql.gz \
    config_${TIMESTAMP}.tar.gz \
    $(find "$BACKUP_ROOT" -name "*_${TIMESTAMP}.tar.gz" -exec basename {} \; | grep -v "${BACKUP_NAME}.tar.gz")

# Encrypt backup if enabled
if [ "$ENCRYPT_BACKUP" = "true" ] && [ -f "$ENCRYPT_KEY" ]; then
    log "Encrypting backup"
    openssl enc -aes-256-cbc -salt -in "$BACKUP_ROOT/${BACKUP_NAME}.tar.gz" \
        -out "$BACKUP_ROOT/${BACKUP_NAME}.tar.gz.enc" \
        -kfile "$ENCRYPT_KEY"
    rm "$BACKUP_ROOT/${BACKUP_NAME}.tar.gz"
    BACKUP_FILE="$BACKUP_ROOT/${BACKUP_NAME}.tar.gz.enc"
else
    BACKUP_FILE="$BACKUP_ROOT/${BACKUP_NAME}.tar.gz"
fi

# Upload to S3 if configured
if command -v aws &> /dev/null && [ -n "$S3_BUCKET" ]; then
    log "Uploading backup to S3"
    aws s3 cp "$BACKUP_FILE" "s3://$S3_BUCKET/$S3_PREFIX" --storage-class STANDARD_IA

    # Verify upload
    if aws s3 ls "s3://$S3_BUCKET/$S3_PREFIX$(basename "$BACKUP_FILE")" > /dev/null; then
        log "Backup uploaded successfully to S3"
    else
        log "ERROR: Failed to upload backup to S3"
        exit 1
    fi
fi

# Calculate and store checksum
log "Calculating backup checksum"
sha256sum "$BACKUP_FILE" > "${BACKUP_FILE}.sha256"

# Cleanup temporary files
log "Cleaning up temporary files"
rm -f "$BACKUP_ROOT/database_${TIMESTAMP}.sql.gz"
rm -f "$BACKUP_ROOT/config_${TIMESTAMP}.tar.gz"
find "$BACKUP_ROOT" -name "*_${TIMESTAMP}.tar.gz" -delete

# Rotate old backups
log "Rotating old backups"
find "$BACKUP_ROOT" -name "sibali_backup_*.tar.gz*" -mtime +$RETENTION_DAYS -delete

# If using S3, clean up old S3 backups
if command -v aws &> /dev/null && [ -n "$S3_BUCKET" ]; then
    log "Cleaning up old S3 backups"
    aws s3api list-objects-v2 --bucket "$S3_BUCKET" --prefix "$S3_PREFIX" \
        --query 'Contents[?LastModified<`'"$(date -d "$RETENTION_DAYS days ago" +%Y-%m-%d)"'`].Key' \
        --output text | xargs -I {} aws s3 rm "s3://$S3_BUCKET/{}" 2>/dev/null || true
fi

# Verify backup integrity
log "Verifying backup integrity"
if [ -f "$BACKUP_FILE" ]; then
    if [ "$ENCRYPT_BACKUP" = "true" ]; then
        # For encrypted backups, just check file exists
        log "Backup created successfully: $BACKUP_FILE (encrypted)"
    else
        # For unencrypted, verify tar integrity
        if tar -tzf "$BACKUP_FILE" > /dev/null; then
            log "Backup integrity verified: $BACKUP_FILE"
        else
            log "ERROR: Backup integrity check failed"
            exit 1
        fi
    fi
else
    log "ERROR: Backup file not found"
    exit 1
fi

log "Full backup completed successfully: $BACKUP_FILE"

# Send notification
# curl -X POST -H 'Content-type: application/json' --data '{"text":"Full backup completed successfully"}' YOUR_SLACK_WEBHOOK
