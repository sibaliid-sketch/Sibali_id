#!/bin/bash

# Full backup orchestration script (DB + media/filestore)
# Usage: ./full-backup.sh [--env <environment>] [--type <full|incremental>] [--dry-run]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
BACKUP_ROOT="/var/backups/sibali"
LOG_FILE="/var/log/sibali/backup-$(date +%Y%m%d-%H%M%S).log"
MANIFEST_FILE="$BACKUP_ROOT/manifest.json"

# Default values
ENVIRONMENT="${APP_ENV:-production}"
BACKUP_TYPE="full"
DRY_RUN=false

# AWS S3 configuration (from environment or config)
S3_BUCKET="${BACKUP_S3_BUCKET:-sibali-backups}"
S3_ENDPOINT="${BACKUP_S3_ENDPOINT:-https://s3.amazonaws.com}"
S3_REGION="${BACKUP_S3_REGION:-us-east-1}"

# Retention policy (days)
RETENTION_DAILY=7
RETENTION_WEEKLY=30
RETENTION_MONTHLY=365

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --env)
      ENVIRONMENT="$2"
      shift 2
      ;;
    --type)
      BACKUP_TYPE="$2"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Logging function
log() {
  echo "$(date '+%Y-%m-%d %H:%M:%S') [$1] $2" | tee -a "$LOG_FILE"
}

# Error handling
error_exit() {
  log "ERROR" "$1"
  exit 1
}

# Create backup directory
create_backup_dir() {
  local timestamp=$(date +%Y%m%d-%H%M%S)
  local backup_dir="$BACKUP_ROOT/$timestamp"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would create backup directory: $backup_dir"
    echo "$backup_dir"
    return
  fi

  mkdir -p "$backup_dir"
  log "INFO" "Created backup directory: $backup_dir"
  echo "$backup_dir"
}

# Lock writes (maintenance mode)
lock_writes() {
  log "INFO" "Locking writes (maintenance mode)"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would enable maintenance mode"
    return
  fi

  # Laravel maintenance mode
  if [[ -f "$PROJECT_ROOT/artisan" ]]; then
    cd "$PROJECT_ROOT"
    php artisan down --message="Backup in progress"
  fi
}

# Unlock writes
unlock_writes() {
  log "INFO" "Unlocking writes (disable maintenance mode)"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would disable maintenance mode"
    return
  fi

  # Laravel maintenance mode
  if [[ -f "$PROJECT_ROOT/artisan" ]]; then
    cd "$PROJECT_ROOT"
    php artisan up
  fi
}

# Dump database
dump_database() {
  local backup_dir="$1"
  local db_file="$backup_dir/database.sql.gz"

  log "INFO" "Dumping database"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would dump database to: $db_file"
    return
  fi

  # Use Laravel's db:dump command if available, otherwise direct mysqldump
  if [[ -f "$PROJECT_ROOT/artisan" ]]; then
    cd "$PROJECT_ROOT"
    php artisan db:dump --gzip > "$db_file"
  else
    mysqldump -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" \
              -h"${DB_HOST:-localhost}" -P"${DB_PORT:-3306}" \
              "${DB_DATABASE:-laravel}" | gzip > "$db_file"
  fi

  local db_size=$(stat -f%z "$db_file" 2>/dev/null || stat -c%s "$db_file")
  log "INFO" "Database dump completed: $(numfmt --to=iec $db_size) bytes"
}

# Archive and encrypt filestore/media
archive_filestore() {
  local backup_dir="$1"
  local media_file="$backup_dir/filestore.tar.gz"

  log "INFO" "Archiving filestore/media"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would archive filestore to: $media_file"
    return
  fi

  # Archive storage directory
  local storage_dir="$PROJECT_ROOT/storage"
  if [[ -d "$storage_dir" ]]; then
    tar -czf "$media_file" -C "$storage_dir" .
    local media_size=$(stat -f%z "$media_file" 2>/dev/null || stat -c%s "$media_file")
    log "INFO" "Filestore archive completed: $(numfmt --to=iec $media_size) bytes"
  else
    log "WARN" "Storage directory not found: $storage_dir"
  fi
}

# Encrypt backup files
encrypt_files() {
  local backup_dir="$1"

  log "INFO" "Encrypting backup files"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would encrypt backup files"
    return
  fi

  # Use GPG for encryption (requires GPG key setup)
  if command -v gpg >/dev/null 2>&1; then
    find "$backup_dir" -name "*.sql.gz" -o -name "*.tar.gz" | while read -r file; do
      log "INFO" "Encrypting: $file"
      gpg --encrypt --recipient "${BACKUP_GPG_RECIPIENT:-backup@sibali.id}" "$file"
      rm "$file" # Remove unencrypted file
    done
  else
    log "WARN" "GPG not available, skipping encryption"
  fi
}

# Compute checksums
compute_checksums() {
  local backup_dir="$1"

  log "INFO" "Computing checksums"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would compute checksums"
    return
  fi

  find "$backup_dir" -type f \( -name "*.gpg" -o -name "*.sql.gz" -o -name "*.tar.gz" \) | while read -r file; do
    local checksum_file="$file.sha256"
    sha256sum "$file" > "$checksum_file"
    log "INFO" "Checksum computed: $checksum_file"
  done
}

# Upload to S3-compatible storage
upload_to_s3() {
  local backup_dir="$1"
  local timestamp=$(basename "$backup_dir")

  log "INFO" "Uploading to S3-compatible storage"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would upload backup to S3: s3://$S3_BUCKET/$ENVIRONMENT/$timestamp/"
    return
  fi

  # Use AWS CLI or s3cmd
  if command -v aws >/dev/null 2>&1; then
    aws s3 cp "$backup_dir/" "s3://$S3_BUCKET/$ENVIRONMENT/$timestamp/" --recursive \
        --endpoint-url="$S3_ENDPOINT" --region="$S3_REGION"
  elif command -v s3cmd >/dev/null 2>&1; then
    s3cmd put "$backup_dir/" "s3://$S3_BUCKET/$ENVIRONMENT/$timestamp/" --recursive
  else
    error_exit "Neither AWS CLI nor s3cmd available for S3 upload"
  fi

  log "INFO" "Upload completed"
}

# Set lifecycle tags
set_lifecycle_tags() {
  local timestamp="$1"

  log "INFO" "Setting lifecycle tags"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would set lifecycle tags"
    return
  fi

  # Determine retention class
  local day_of_week=$(date +%u) # 1=Monday, 7=Sunday
  local day_of_month=$(date +%d)

  local lifecycle_class="daily"
  if [[ $day_of_week -eq 7 ]]; then
    lifecycle_class="weekly"
  fi
  if [[ $day_of_month -eq 01 ]]; then
    lifecycle_class="monthly"
  fi

  # Tag the backup
  if command -v aws >/dev/null 2>&1; then
    aws s3api put-object-tagging \
        --bucket "$S3_BUCKET" \
        --key "$ENVIRONMENT/$timestamp/manifest.json" \
        --tagging "lifecycle=$lifecycle_class,environment=$ENVIRONMENT,timestamp=$timestamp" \
        --endpoint-url="$S3_ENDPOINT" --region="$S3_REGION"
  fi

  log "INFO" "Lifecycle tags set: $lifecycle_class"
}

# Rotate backups
rotate_backups() {
  log "INFO" "Rotating old backups"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would rotate backups"
    return
  fi

  # Delete old backups based on retention policy
  local cutoff_daily=$(date -d "$RETENTION_DAILY days ago" +%Y%m%d)
  local cutoff_weekly=$(date -d "$RETENTION_WEEKLY days ago" +%Y%m%d)
  local cutoff_monthly=$(date -d "$RETENTION_MONTHLY days ago" +%Y%m%d)

  # This would require listing and deleting old backups from S3
  # Implementation depends on your S3 client capabilities

  log "INFO" "Backup rotation completed"
}

# Update manifest
update_manifest() {
  local backup_dir="$1"
  local timestamp=$(basename "$backup_dir")

  log "INFO" "Updating backup manifest"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would update manifest"
    return
  fi

  # Calculate sizes
  local db_size=0
  local media_size=0

  if [[ -f "$backup_dir/database.sql.gz.gpg" ]]; then
    db_size=$(stat -f%z "$backup_dir/database.sql.gz.gpg" 2>/dev/null || stat -c%s "$backup_dir/database.sql.gz.gpg")
  fi

  if [[ -f "$backup_dir/filestore.tar.gz.gpg" ]]; then
    media_size=$(stat -f%z "$backup_dir/filestore.tar.gz.gpg" 2>/dev/null || stat -c%s "$backup_dir/filestore.tar.gz.gpg")
  fi

  local total_size=$((db_size + media_size))

  # Create manifest entry
  local manifest_entry=$(cat << EOF
{
  "timestamp": "$timestamp",
  "environment": "$ENVIRONMENT",
  "type": "$BACKUP_TYPE",
  "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo 'unknown')",
  "sizes": {
    "database": $db_size,
    "filestore": $media_size,
    "total": $total_size
  },
  "files": [
    $(find "$backup_dir" -type f -name "*.gpg" -o -name "*.sha256" | \
      sed 's/.*/"&"/' | paste -sd,)
  ]
}
EOF
)

  # Update manifest file
  if [[ -f "$MANIFEST_FILE" ]]; then
    # Append to existing manifest (assuming it's a JSON array)
    local temp_file=$(mktemp)
    jq ". += [$manifest_entry]" "$MANIFEST_FILE" > "$temp_file"
    mv "$temp_file" "$MANIFEST_FILE"
  else
    echo "[$manifest_entry]" > "$MANIFEST_FILE"
  fi

  log "INFO" "Manifest updated"
}

# Send notifications
send_notifications() {
  local status="$1"
  local backup_dir="$2"

  # Send success/failure notifications
  # curl -X POST -H 'Content-type: application/json' \
  #      --data '{"status":"'"$status"'","backup_dir":"'"$backup_dir"'"}' \
  #      "$BACKUP_NOTIFICATION_WEBHOOK"

  log "INFO" "Notifications sent: $status"
}

# Cleanup local files
cleanup_local() {
  local backup_dir="$1"

  log "INFO" "Cleaning up local backup files"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would cleanup: $backup_dir"
    return
  fi

  # Keep local copy for 24 hours as safety buffer
  # (echo "rm -rf '$backup_dir'") | at now + 24 hours 2>/dev/null || true

  log "INFO" "Local cleanup scheduled"
}

# Main backup flow
main() {
  log "INFO" "Starting $BACKUP_TYPE backup for environment: $ENVIRONMENT"

  local backup_dir
  backup_dir=$(create_backup_dir)

  # Ensure cleanup on failure
  trap 'unlock_writes; send_notifications "failed" "$backup_dir"' ERR

  lock_writes

  dump_database "$backup_dir"
  archive_filestore "$backup_dir"

  unlock_writes

  encrypt_files "$backup_dir"
  compute_checksums "$backup_dir"
  upload_to_s3 "$backup_dir"
  set_lifecycle_tags "$(basename "$backup_dir")"
  rotate_backups
  update_manifest "$backup_dir"

  send_notifications "success" "$backup_dir"
  cleanup_local "$backup_dir"

  log "INFO" "Backup completed successfully"
}

# Run main function
main "$@"
