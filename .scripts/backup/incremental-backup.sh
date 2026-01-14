#!/bin/bash

# Incremental Backup Script for Sibali.id
# Purpose: Jobs incremental untuk efisiensi storage dan menurunkan RTO pada restore
# Function: Mengurangi storage & network usage; memungkinkan point-in-time recovery lebih granular

set -e

# Configuration
BACKUP_ROOT="/var/backups/sibali"
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-backup_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"
MYSQL_DATA_DIR="${MYSQL_DATA_DIR:-/var/lib/mysql}"
RSYNC_SOURCE="${RSYNC_SOURCE:-/var/www/html/storage/app}"
RSYNC_DEST_BASE="${BACKUP_ROOT}/files"
LOG_FILE="${BACKUP_ROOT}/logs/incremental_backup.log"

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Check if base backup exists
if [ ! -d "${BACKUP_ROOT}/base" ]; then
    log "ERROR: Base backup not found at ${BACKUP_ROOT}/base. Please run full backup first."
    exit 1
fi

# Create incremental backup directory
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
INCREMENTAL_DIR="${BACKUP_ROOT}/incremental_${TIMESTAMP}"
mkdir -p "$INCREMENTAL_DIR"

log "Starting incremental backup: $INCREMENTAL_DIR"

# Database incremental backup via binlog
log "Copying MySQL binlogs..."
if [ -d "$MYSQL_DATA_DIR" ]; then
    # Get current binlog position
    BINLOG_INFO=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW MASTER STATUS\G" 2>/dev/null | grep -E "(File|Position)" | sed 's/.*: //')
    BINLOG_FILE=$(echo "$BINLOG_INFO" | head -1)
    BINLOG_POS=$(echo "$BINLOG_INFO" | tail -1)

    # Copy binlogs since last backup
    # This is a simplified version - in production, track last binlog position
    cp -r "$MYSQL_DATA_DIR"/*.index "$INCREMENTAL_DIR/" 2>/dev/null || true
    find "$MYSQL_DATA_DIR" -name "mysql-bin.*" -newer "${BACKUP_ROOT}/base/timestamp" -exec cp {} "$INCREMENTAL_DIR/" \; 2>/dev/null || true

    echo "Last Binlog: $BINLOG_FILE Position: $BINLOG_POS" > "$INCREMENTAL_DIR/binlog_position.txt"
else
    log "WARNING: MySQL data directory not found, skipping binlog backup"
fi

# File incremental backup using rsync
log "Performing incremental file backup..."
mkdir -p "$INCREMENTAL_DIR/files"

if rsync -a --link-dest="${BACKUP_ROOT}/base/files" --exclude='*.tmp' --exclude='cache/' "$RSYNC_SOURCE/" "$INCREMENTAL_DIR/files/"; then
    log "File backup completed successfully"
else
    log "ERROR: File backup failed"
    exit 1
fi

# Create manifest
cat > "$INCREMENTAL_DIR/manifest.txt" << EOF
Backup Type: Incremental
Timestamp: $TIMESTAMP
Base Backup: ${BACKUP_ROOT}/base
Source Directories:
  - Database: $MYSQL_DATA_DIR
  - Files: $RSYNC_SOURCE
Binlog Position: $BINLOG_FILE $BINLOG_POS
Created: $(date)
EOF

# Verify backup integrity
log "Verifying backup integrity..."
if [ -f "$INCREMENTAL_DIR/manifest.txt" ] && [ -d "$INCREMENTAL_DIR/files" ]; then
    # Check for missing segments (simplified)
    if [ -f "$INCREMENTAL_DIR/binlog_position.txt" ]; then
        log "Backup verification passed"
    else
        log "WARNING: Binlog position not captured"
    fi
else
    log "ERROR: Backup verification failed"
    exit 1
fi

# Update latest incremental link
ln -sfn "$INCREMENTAL_DIR" "${BACKUP_ROOT}/latest_incremental"

log "Incremental backup completed successfully: $INCREMENTAL_DIR"

# Optional: Clean up old incrementals (keep last 30 days)
find "$BACKUP_ROOT" -name "incremental_*" -type d -mtime +30 -exec rm -rf {} \; 2>/dev/null || true

exit 0
