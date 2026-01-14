#!/bin/bash

# Incremental Backup Script for Sibali.id
# Jobs incremental untuk efisiensi storage dan menurunkan RTO pada restore
# Mengurangi storage & network usage; memungkinkan point-in-time recovery lebih granular

set -euo pipefail

# Configuration
BACKUP_ROOT="/var/backups/sibali"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-backup_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-sibaliid}"
FILE_ROOT="/var/www/sibali.id"
LOG_FILE="/var/log/sibali-backup.log"

# Modes: DB incremental via WAL shipping / binlog capture; file deltas via rsync --link-dest or snapshot diffs
MODE="${1:-full}"  # full, db, files
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="${BACKUP_ROOT}/${TIMESTAMP}"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Ensure base full-backup exists; maintain sequence numbers and manifest for applying increments in order
check_base_backup() {
    if [[ "$MODE" == "db" || "$MODE" == "files" ]]; then
        if [[ ! -f "${BACKUP_ROOT}/base_full/manifest.txt" ]]; then
            log "ERROR: Base full backup not found. Run full backup first."
            exit 1
        fi
    fi
}

# Safety: automatic verification of continuity (no missing segments), alert on gap
verify_continuity() {
    local last_seq
    last_seq=$(find "$BACKUP_ROOT" -name "sequence_*.txt" -type f | sort | tail -1 | xargs basename | sed 's/sequence_\(.*\)\.txt/\1/')
    if [[ -n "$last_seq" && "$last_seq" -ne $(( $(date +%s) - 86400 )) ]]; then
        log "WARNING: Gap detected in backup sequence. Last: $last_seq, Current: $(date +%s)"
        # Send alert (implement notification)
    fi
}

# DB incremental via WAL shipping / binlog capture
backup_db_incremental() {
    log "Starting DB incremental backup"
    mkdir -p "$BACKUP_DIR/db"

    # Capture binlog position
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW MASTER STATUS;" > "$BACKUP_DIR/db/binlog_position.txt"

    # Flush logs to create new binlog
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "FLUSH LOGS;"

    # Copy incremental binlogs
    rsync -av --link-dest="$BACKUP_ROOT/base_full/db/binlogs/" /var/lib/mysql/binlogs/ "$BACKUP_DIR/db/binlogs/"

    log "DB incremental backup completed"
}

# File deltas via rsync --link-dest or snapshot diffs
backup_files_incremental() {
    log "Starting files incremental backup"
    mkdir -p "$BACKUP_DIR/files"

    # Use rsync with --link-dest for hard links to unchanged files
    rsync -av --link-dest="$BACKUP_ROOT/base_full/files/" "$FILE_ROOT/" "$BACKUP_DIR/files/"

    log "Files incremental backup completed"
}

# Full backup (base)
backup_full() {
    log "Starting full backup"
    mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/files"

    # DB dump
    mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --all-databases --single-transaction --master-data=2 > "$BACKUP_DIR/db/full_dump.sql"

    # Files
    rsync -av "$FILE_ROOT/" "$BACKUP_DIR/files/"

    # Create manifest
    echo "Full backup created on $TIMESTAMP" > "$BACKUP_DIR/manifest.txt"
    echo "DB: $BACKUP_DIR/db/full_dump.sql" >> "$BACKUP_DIR/manifest.txt"
    echo "Files: $BACKUP_DIR/files/" >> "$BACKUP_DIR/manifest.txt"

    # Update base reference
    ln -sfn "$BACKUP_DIR" "$BACKUP_ROOT/base_full"

    log "Full backup completed"
}

# Main execution
main() {
    log "Starting incremental backup - Mode: $MODE"

    verify_continuity

    case "$MODE" in
        full)
            backup_full
            ;;
        db)
            check_base_backup
            backup_db_incremental
            ;;
        files)
            check_base_backup
            backup_files_incremental
            ;;
        *)
            log "ERROR: Invalid mode. Use: full, db, or files"
            exit 1
            ;;
    esac

    # Update sequence number
    echo "$TIMESTAMP" > "$BACKUP_ROOT/sequence_${TIMESTAMP}.txt"

    # Compress and encrypt if needed
    # tar -czf "$BACKUP_DIR.tar.gz" -C "$BACKUP_ROOT" "$TIMESTAMP"
    # gpg --encrypt "$BACKUP_DIR.tar.gz"

    log "Backup completed successfully"
}

main "$@"
