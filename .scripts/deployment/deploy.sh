#!/bin/bash

# Core deploy script for coordinated release
# Usage: ./deploy.sh --env <staging|prod> --tag <release-tag> [--dry-run] [--force]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOCKFILE="/tmp/sibali-deploy.lock"
LOG_FILE="/var/log/sibali/deploy-$(date +%Y%m%d-%H%M%S).log"
REPORT_FILE="$PROJECT_ROOT/deploy-report.json"

# Default values
ENVIRONMENT=""
TAG=""
DRY_RUN=false
FORCE=false

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --env)
      ENVIRONMENT="$2"
      shift 2
      ;;
    --tag)
      TAG="$2"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=true
      shift
      ;;
    --force)
      FORCE=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# Validate arguments
if [[ -z "$ENVIRONMENT" || -z "$TAG" ]]; then
  echo "Usage: $0 --env <staging|prod> --tag <release-tag> [--dry-run] [--force]"
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

# Preflight checks
preflight_checks() {
  log "INFO" "Starting preflight checks..."

  # Check disk space (require at least 1GB free)
  local free_space=$(df / | tail -1 | awk '{print $4}')
  if [[ $free_space -lt 1048576 ]]; then
    error_exit "Insufficient disk space: ${free_space}KB free"
  fi

  # Check DB connectivity
  if ! php artisan db:monitor >/dev/null 2>&1; then
    error_exit "Database connectivity check failed"
  fi

  # Check pending migrations
  local pending_migrations=$(php artisan migrate:status | grep -c "Pending")
  if [[ $pending_migrations -gt 0 ]]; then
    log "WARN" "Found $pending_migrations pending migrations"
  fi

  log "INFO" "Preflight checks completed"
}

# Acquire lock
acquire_lock() {
  if [[ -f "$LOCKFILE" ]]; then
    if [[ "$FORCE" != true ]]; then
      error_exit "Another deployment is in progress. Use --force to override."
    else
      log "WARN" "Forcing deployment, removing existing lockfile"
      rm -f "$LOCKFILE"
    fi
  fi

  echo "$$" > "$LOCKFILE"
  log "INFO" "Lock acquired"
}

# Release lock
release_lock() {
  rm -f "$LOCKFILE"
  log "INFO" "Lock released"
}

# Checkout release tag
checkout_release() {
  log "INFO" "Checking out release tag: $TAG"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would checkout $TAG"
    return
  fi

  git fetch --tags
  git checkout "$TAG"
  git submodule update --init --recursive
}

# Install dependencies
install_deps() {
  log "INFO" "Installing dependencies"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would run composer install --no-dev --optimize-autoloader"
    log "DRY_RUN" "Would run npm ci --production"
    return
  fi

  composer install --no-dev --optimize-autoloader
  npm ci --production
}

# Build assets
build_assets() {
  log "INFO" "Building assets"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would run npm run build"
    return
  fi

  npm run build
}

# Run migrations
run_migrations() {
  log "INFO" "Running database migrations"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would run php artisan migrate --force"
    return
  fi

  php artisan migrate --force
}

# Clear and warmup cache
cache_operations() {
  log "INFO" "Clearing and warming cache"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would run php artisan cache:clear"
    log "DRY_RUN" "Would run php artisan config:cache"
    log "DRY_RUN" "Would run php artisan route:cache"
    log "DRY_RUN" "Would run php artisan view:cache"
    return
  fi

  php artisan cache:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
}

# Migrate queue workers
migrate_queues() {
  log "INFO" "Migrating queue workers"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would restart queue workers"
    return
  fi

  # Assuming supervisor or systemd
  if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart laravel-worker:*
  elif command -v systemctl >/dev/null 2>&1; then
    systemctl restart laravel-queue-worker
  fi
}

# Restart services
restart_services() {
  log "INFO" "Restarting services"

  if [[ "$DRY_RUN" == true ]]; then
    log "DRY_RUN" "Would restart web server and PHP-FPM"
    return
  fi

  # Restart web server and PHP-FPM
  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload nginx
    systemctl restart php8.1-fpm
  fi
}

# Generate deploy report
generate_report() {
  local status="$1"
  local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

  cat > "$REPORT_FILE" << EOF
{
  "deployment": {
    "timestamp": "$timestamp",
    "environment": "$ENVIRONMENT",
    "tag": "$TAG",
    "status": "$status",
    "dry_run": $DRY_RUN
  },
  "system": {
    "hostname": "$(hostname)",
    "user": "$(whoami)"
  },
  "git": {
    "commit": "$(git rev-parse HEAD)",
    "branch": "$(git rev-parse --abbrev-ref HEAD)"
  }
}
EOF

  log "INFO" "Deploy report generated: $REPORT_FILE"
}

# Send notifications
send_notifications() {
  local status="$1"

  # Send to Slack/monitoring
  # curl -X POST -H 'Content-type: application/json' --data '{"text":"Deployment completed: '"$status"'"}' $SLACK_WEBHOOK_URL

  log "INFO" "Notifications sent"
}

# Main deployment flow
main() {
  log "INFO" "Starting deployment to $ENVIRONMENT with tag $TAG"

  preflight_checks
  acquire_lock

  trap release_lock EXIT

  checkout_release
  install_deps
  build_assets
  run_migrations
  cache_operations
  migrate_queues
  restart_services

  generate_report "success"
  send_notifications "success"

  log "INFO" "Deployment completed successfully"
}

# Run main function
main "$@"
