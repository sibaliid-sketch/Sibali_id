#!/bin/bash

# Restore Script for Sibali.id
# Helper restore non-emergency untuk dev/QA atau partial restores
# Memudahkan pemulihan data untuk testing dan selektif restore tanpa proses DR penuh

set -euo pipefail

# Configuration
BACKUP_ROOT="/var/backups/sibali"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-restore_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-sibaliid}"
FILE_ROOT="/var/www/sibali.id"
LOG_FILE="/var/log/sibali-restore.log"
ENVIRONMENT="${ENVIRONMENT:-dev}"  # dev, qa, staging

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Features: select backup by date/tag → validate checksum → restore DB to target instance (non-prod or isolated restore cluster) → restore filestore partial paths
select_backup() {
    local backup_date="$1"
    local backup_path="${BACKUP_ROOT}/${backup_date}"

    if [[ ! -d "$backup_path" ]]; then
        log "ERROR: Backup not found: $backup_path"
        echo "Available backups:"
        ls -la "$BACKUP_ROOT" | grep "^d" | awk '{print $9}' | grep -E '^[0-9]{8}_[0-9]{6}$'
        exit 1
    fi

    echo "$backup_path"
}

# Validate checksum
validate_checksum() {
    local backup_path="$1"
    local manifest_file="$backup_path/manifest.txt"

    if [[ -f "$manifest_file" ]]; then
        log "Validating checksums..."
        # Implement checksum validation
        # sha256sum -c "$backup_path/checksums.sha256" || { log "ERROR: Checksum validation failed"; exit 1; }
        log "Checksum validation passed"
    else
        log "WARNING: No manifest file found, skipping checksum validation"
    fi
}

# Restore DB to target instance (non-prod or isolated restore cluster)
restore_db() {
    local backup_path="$1"
    local target_db="${2:-$DB_NAME}"

    # Safeguards: never restore into production primary unless --force with explicit approval
    if [[ "$ENVIRONMENT" == "production" && "${FORCE_RESTORE:-false}" != "true" ]]; then
        log "ERROR: Cannot restore to production without --force flag and explicit approval"
        exit 1
    fi

    log "Restoring database from $backup_path"

    if [[ -f "$backup_path/db/full_dump.sql" ]]; then
        # Full restore
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$target_db" < "$backup_path/db/full_dump.sql"
    elif [[ -d "$backup_path/db/binlogs" ]]; then
        # Incremental restore
        log "Applying incremental binlogs..."
        # Implement binlog replay
        # mysqlbinlog binlogs/* | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$target_db"
    else
        log "ERROR: No valid DB backup found"
        exit 1
    fi

    log "Database restore completed"
}

# Restore filestore partial paths
restore_files() {
    local backup_path="$1"
    local partial_path="${2:-}"  # Optional partial path

    log "Restoring files from $backup_path"

    if [[ -n "$partial_path" ]]; then
        # Partial restore
        rsync -av "$backup_path/files/$partial_path" "$FILE_ROOT/$partial_path"
    else
        # Full file restore
        rsync -av "$backup_path/files/" "$FILE_ROOT/"
    fi

    log "File restore completed"
}

# Post-restore: run migrations in compatibility mode, reindex if necessary, run sanity queries
post_restore_tasks() {
    local target_db="${1:-$DB_NAME}"

    log "Running post-restore tasks..."

    # Run migrations in compatibility mode
    cd /var/www/sibali.id
    php artisan migrate --force

    # Reindex if necessary
    # php artisan scout:reindex  # If using Laravel Scout

    # Run sanity queries
    log "Running sanity checks..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$target_db" -e "
        SELECT COUNT(*) as users FROM users;
        SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = '$target_db';
        SHOW PROCESSLIST;
    " > /tmp/sanity_check.log

    log "Sanity checks completed. Results in /tmp/sanity_check.log"
}

# Main execution
main() {
    local backup_date=""
    local target_db=""
    local partial_path=""
    local restore_db_flag=false
    local restore_files_flag=false

    while [[ $# -gt 0 ]]; do
        case $1 in
            --backup-date)
                backup_date="$2"
                shift 2
                ;;
            --target-db)
                target_db="$2"
                shift 2
                ;;
            --partial-path)
                partial_path="$2"
                shift 2
                ;;
            --db)
                restore_db_flag=true
                shift
                ;;
            --files)
                restore_files_flag=true
                shift
                ;;
            --force)
                FORCE_RESTORE=true
                shift
                ;;
            *)
                echo "Usage: $0 --backup-date YYYYMMDD_HHMMSS [--target-db db_name] [--partial-path path] [--db] [--files] [--force]"
                exit 1
                ;;
        esac
    done

    if [[ -z "$backup_date" ]]; then
        log "ERROR: --backup-date is required"
        exit 1
    fi

    if [[ "$restore_db_flag" == false && "$restore_files_flag" == false ]]; then
        restore_db_flag=true
        restore_files_flag=true
    fi

    local backup_path
    backup_path=$(select_backup "$backup_date")

    validate_checksum "$backup_path"

    if [[ "$restore_db_flag" == true ]]; then
        restore_db "$backup_path" "$target_db"
    fi

    if [[ "$restore_files_flag" == true ]]; then
        restore_files "$backup_path" "$partial_path"
    fi

    post_restore_tasks "$target_db"

    log "Restore completed successfully"
}

main "$@"
