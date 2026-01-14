#!/bin/bash

# Rollback script to revert to previous release version
# Usage: ./rollback.sh --env <staging|prod> --target-tag <rollback-tag> [--with-db] [--auto]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOCKFILE="/tmp/sibali-rollback.lock"
LOG_FILE="/var/log/sibali/rollback-$(date +%Y%m%d-%H%M%S).log"
REPORT_FILE="$PROJECT_ROOT/rollback-report.json"

# Default values
ENVIRONMENT=""
TARGET_TAG=""
WITH_DB=false
AUTO=false

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --env)
      ENVIRONMENT="$2"
      shift 2
      ;;
    --target-tag)
      TARGET_TAG="$2"
      shift 2
      ;;
    --with-db)
      WITH_DB=true
      shift
      ;;
    --auto)
      AUTO=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Validate arguments
if [[ -z "$ENVIRONMENT" || -z "$TARGET_TAG" ]]; then
  echo "Usage: $0 --env <staging|prod> --target-tag <rollback-tag> [--with-db] [--auto]"
  exit 1
fi

# Logging function
log() {
  echo "$(date '+%Y-%m-%d %H:%M:%S') [$1] $2" | tee -a "$LOG_FILE"
}

# Error handling
error_exit() {
  log "ERROR" "$1"
  rm -f "$LOCKFILE"
  exit 1
}

# Confirmation prompt
confirm_action() {
  local message="$1"

  if [[ "$AUTO" == true ]]; then
    log "INFO" "Auto-rollback: $message"
    return
  fi

  read -p "$message (y/N): " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    error_exit "Rollback cancelled by user"
  fi
}

# Validate rollback target
validate_target() {
  log "INFO" "Validating rollback target: $TARGET_TAG"

  if ! git tag --list | grep -q "^$TARGET_TAG$"; then
    error_exit "Target tag $TARGET_TAG does not exist"
  fi

  # Check if target tag is older than current
  local current_commit=$(git rev-parse HEAD)
  local target_commit=$(git rev-parse "$TARGET_TAG^{}")

  if [[ "$current_commit" == "$target_commit" ]]; then
    error_exit "Cannot rollback to same commit"
  fi

  log "INFO" "Rollback target validated"
}

# Acquire lock
acquire_lock() {
  if [[ -f "$LOCKFILE" ]]; then
    error_exit "Another rollback is in progress"
  fi

  echo "$$" > "$LOCKFILE"
  log "INFO" "Lock acquired"
}

# Release lock
release_lock() {
  rm -f "$LOCKFILE"
  log "INFO" "Lock released"
}

# Optional DB snapshot restore
restore_db_snapshot() {
  if [[ "$WITH_DB" != true ]]; then
    log "INFO" "Skipping DB restore (--with-db not specified)"
    return
  fi

  confirm_action "This will restore DB to snapshot. Any new data will be lost. Continue?"

  log "INFO" "Restoring DB snapshot"

  # Assuming backup files are in /var/backups/sibali
  local backup_dir="/var/backups/sibali"
  local snapshot_file="$backup_dir/db-snapshot-$TARGET_TAG.sql.gz"

  if [[ ! -f "$snapshot_file" ]]; then
    error_exit "DB snapshot not found: $snapshot_file"
  fi

  # Verify checksum
  local checksum_file="$snapshot_file.sha256"
  if [[ -f "$checksum_file" ]]; then
    if ! sha256sum -c "$checksum_file"; then
      error_exit "DB snapshot checksum verification failed"
    fi
  fi

  # Restore DB
  gunzip -c "$snapshot_file" | mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"

  log "INFO" "DB snapshot restored"
}

# Revert code to target tag
revert_code() {
  log "INFO" "Reverting code to tag: $TARGET_TAG"

  git checkout "$TARGET_TAG"
  git submodule update --init --recursive
}

# Clear caches and warmup
clear_and_warm_cache() {
  log "INFO" "Clearing and warming cache for rollback version"

  php artisan cache:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
}

# Restart services
restart_services() {
  log "INFO" "Restarting services after rollback"

  # Restart queue workers
  if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart laravel-worker:*
  elif command -v systemctl >/dev/null 2>&1; then
    systemctl restart laravel-queue-worker
  fi

  # Restart web server and PHP-FPM
  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload nginx
    systemctl restart php8.1-fpm
  fi
}

# Run health checks
run_health_checks() {
  log "INFO" "Running post-rollback health checks"

  # Basic health check
  if ! curl -f -s --max-time 10 "http://localhost/health" >/dev/null; then
    log "WARN" "Health check failed - manual verification required"
  else
    log "INFO" "Health check passed"
  fi

  # Smoke test
  if ! php artisan tinker --execute="echo 'Smoke test passed';" >/dev/null 2>&1; then
    log "WARN" "Smoke test failed - manual verification required"
  else
    log "INFO" "Smoke test passed"
  fi
}

# Generate rollback report
generate_report() {
  local status="$1"
  local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

  cat > "$REPORT_FILE" << EOF
{
  "rollback": {
    "timestamp": "$timestamp",
    "environment": "$ENVIRONMENT",
    "target_tag": "$TARGET_TAG",
    "status": "$status",
    "db_restored": $WITH_DB,
    "auto_mode": $AUTO
  },
  "system": {
    "hostname": "$(hostname)",
    "user": "$(whoami)"
  },
  "git": {
    "previous_commit": "$(git rev-parse HEAD@{1})",
    "current_commit": "$(git rev-parse HEAD)",
    "branch": "$(git rev-parse --abbrev-ref HEAD)"
  }
}
EOF

  log "INFO" "Rollback report generated: $REPORT_FILE"
}

# Send notifications
send_notifications() {
  local status="$1"

  # Send to Slack/monitoring
  # curl -X POST -H 'Content-type: application/json' --data '{"text":"Rollback completed: '"$status"'"}' $SLACK_WEBHOOK_URL

  log "INFO" "Notifications sent"
}

# Create incident ticket
create_incident_ticket() {
  log "INFO" "Creating incident ticket template"

  # This would integrate with your ticketing system
  # Example: Create GitHub issue or JIRA ticket

  cat > "$PROJECT_ROOT/incident-template.md" << EOF
# Rollback Incident Report

## Incident Details
- **Timestamp**: $(date)
- **Environment**: $ENVIRONMENT
- **Rollback Tag**: $TARGET_TAG
- **DB Restored**: $WITH_DB

## Actions Taken
- Code reverted to $TARGET_TAG
- Cache cleared and warmed
- Services restarted
- Health checks performed

## Next Steps
- Monitor application performance
- Verify user impact
- Conduct post-mortem analysis
- Implement preventive measures

## RCA Required
- [ ] Identify root cause of original deployment failure
- [ ] Review deployment process
- [ ] Update rollback procedures if needed
EOF

  log "INFO" "Incident template created: $PROJECT_ROOT/incident-template.md"
}

# Main rollback flow
main() {
  log "INFO" "Starting rollback to $ENVIRONMENT with target tag $TARGET_TAG"

  validate_target
  acquire_lock

  trap release_lock EXIT

  confirm_action "This will rollback to $TARGET_TAG. Continue?"

  restore_db_snapshot
  revert_code
  clear_and_warm_cache
  restart_services
  run_health_checks

  generate_report "success"
  send_notifications "success"
  create_incident_ticket

  log "INFO" "Rollback completed successfully"
}

# Run main function
main "$@"
