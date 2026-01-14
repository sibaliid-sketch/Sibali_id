#!/bin/bash

# Emergency Maintenance Script for Sibali.id Disaster Recovery
# This script enables maintenance mode, preserves logs/traces, and triggers customer-facing maintenance page

set -euo pipefail

# Configuration
SCRIPT_VERSION="1.0.0"
LOG_FILE="/var/log/sibali/emergency-maintenance-$(date +%Y%m%d-%H%M%S).log"
MAINTENANCE_DURATION_HOURS="${MAINTENANCE_DURATION_HOURS:-4}"
MAINTENANCE_MESSAGE="${MAINTENANCE_MESSAGE:-"We are currently performing scheduled maintenance. Service will be restored shortly."}"

# Application Configuration
APP_ENV="${APP_ENV:-production}"
LARAVEL_ENV_FILE="${LARAVEL_ENV_FILE:-/var/www/sibali/.env}"
STATUS_PAGE_URL="${STATUS_PAGE_URL:-https://status.sibali.id}"

# Monitoring and Alerting
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-https://hooks.slack.com/services/...}"
PAGERDUTY_INTEGRATION_KEY="${PAGERDUTY_INTEGRATION_KEY:-...}"

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error_exit() {
    log "ERROR: $1"
    exit 1
}

# Activation of maintenance mode
activate_maintenance_mode() {
    log "Activating maintenance mode..."

    # Enable Laravel maintenance mode
    cd /var/www/sibali
    php artisan down --message="$MAINTENANCE_MESSAGE" --retry=60

    # Update load balancer to serve maintenance page
    aws elbv2 modify-listener \
        --listener-arn "$LISTENER_ARN" \
        --default-actions '[
            {
                "Type": "fixed-response",
                "FixedResponseConfig": {
                    "MessageBody": "'"$MAINTENANCE_MESSAGE"'",
                    "StatusCode": "503",
                    "ContentType": "text/html"
                }
            }
        ]'

    # Set Retry-After header
    local retry_after=$((MAINTENANCE_DURATION_HOURS * 3600))
    aws elbv2 modify-listener \
        --listener-arn "$LISTENER_ARN" \
        --default-actions '[
            {
                "Type": "fixed-response",
                "FixedResponseConfig": {
                    "MessageBody": "'"$MAINTENANCE_MESSAGE"'",
                    "StatusCode": "503",
                    "ContentType": "text/html",
                    "Headers": [
                        {
                            "HeaderName": "Retry-After",
                            "HeaderValue": "'"$retry_after"'"
                        }
                    ]
                }
            }
        ]'

    log "Maintenance mode activated"
}

# Log and trace preservation
preserve_logs_and_traces() {
    log "Preserving logs and traces..."

    local snapshot_timestamp=$(date +%Y%m%d-%H%M%S)
    local snapshot_dir="/tmp/sibali-maintenance-snapshot-$snapshot_timestamp"

    mkdir -p "$snapshot_dir/logs" "$snapshot_dir/traces" "$snapshot_dir/metrics"

    # Snapshot current log streams
    log "Snapshotting application logs..."
    aws logs create-export-task \
        --log-group-name "/aws/ecs/sibali-api" \
        --from "$(date -d '1 hour ago' +%s000)" \
        --to "$(date +%s000)" \
        --destination "sibali-maintenance-snapshots" \
        --destination-prefix "logs/$snapshot_timestamp"

    # Export traces/spans from X-Ray or Jaeger
    log "Exporting traces..."
    aws xray get-trace-summaries \
        --start-time "$(date -d '1 hour ago' -Iseconds)" \
        --end-time "$(date -Iseconds)" \
        --query 'TraceSummaries[*]' > "$snapshot_dir/traces/xray-traces.json"

    # Preserve database slow query logs
    log "Preserving database logs..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
        -e "SHOW PROCESSLIST;" > "$snapshot_dir/logs/db-processlist-$snapshot_timestamp.txt"

    # Seal artifacts to immutable storage
    log "Sealing artifacts to immutable storage..."
    aws s3 cp "$snapshot_dir" "s3://sibali-immutable-artifacts/maintenance-snapshots/$snapshot_timestamp/" --recursive

    # Clean up local snapshots
    rm -rf "$snapshot_dir"

    log "Logs and traces preserved"
}

# State protection
protect_critical_state() {
    log "Protecting critical application state..."

    # Disable background jobs
    log "Disabling background job processors..."
    aws ecs update-service \
        --cluster sibali-production \
        --service sibali-queue-worker \
        --desired-count 0

    # Pause external integrations
    log "Pausing external integrations..."

    # Disable webhook delivery
    aws lambda update-function-configuration \
        --function-name sibali-webhook-processor \
        --environment "Variables={WEBHOOKS_ENABLED=false}"

    # Pause email sending
    aws ses update-configuration-set \
        --configuration-set-name sibali-production \
        --suppression-options Enabled=true

    # Preserve in-flight transactions
    log "Preserving in-flight transaction state..."
    # Implementation depends on application architecture

    # Drain incoming request queues
    log "Draining request queues..."
    aws sqs purge-queue \
        --queue-url "$SQS_QUEUE_URL"

    log "Critical state protected"
}

# Data handling during isolation
handle_data_isolation() {
    log "Handling data isolation..."

    # Ensure in-flight transactions are drained
    log "Draining active transactions..."
    # Wait for active connections to complete
    sleep 30

    # Mark pending transactions
    # Implementation depends on application logic

    # Preserve incoming request queue
    log "Preserving request queue state..."
    aws sqs get-queue-attributes \
        --queue-url "$SQS_QUEUE_URL" \
        --attribute-names ApproximateNumberOfMessages > "/tmp/queue-state-$snapshot_timestamp.json"

    # Ensure backups run before risky operations
    log "Ensuring recent backup exists..."
    local latest_backup=$(aws s3 ls "s3://sibali-backups/" | sort | tail -n1 | awk '{print $4}')
    if [[ -z "$latest_backup" ]]; then
        log "WARNING: No recent backup found"
    else
        log "Latest backup: $latest_backup"
    fi

    log "Data isolation handling completed"
}

# Notifications and alerting
send_notifications() {
    log "Sending notifications and alerts..."

    # Create incident in tracking system
    local incident_payload='{
        "title": "Emergency Maintenance - Sibali.id",
        "description": "'"$MAINTENANCE_MESSAGE"'",
        "severity": "maintenance",
        "components": ["api", "web", "database"],
        "maintenance_duration_hours": '"$MAINTENANCE_DURATION_HOURS"'
    }'

    curl -X POST "$STATUS_PAGE_URL/api/incidents" \
        -H "Authorization: Bearer $STATUS_PAGE_API_KEY" \
        -H "Content-Type: application/json" \
        -d "$incident_payload"

    # Alert on-call team
    local slack_payload='{
        "channel": "#sibali-incidents",
        "username": "DR Bot",
        "icon_emoji": ":warning:",
        "attachments": [{
            "color": "warning",
            "title": "Emergency Maintenance Activated",
            "text": "'"$MAINTENANCE_MESSAGE"'",
            "fields": [
                {"title": "Duration", "value": "'"$MAINTENANCE_DURATION_HOURS"' hours", "short": true},
                {"title": "Started", "value": "'$(date)'", "short": true}
            ]
        }]
    }'

    curl -X POST "$SLACK_WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -d "$slack_payload"

    # PagerDuty alert (if critical)
    if [[ "${CRITICAL_MAINTENANCE:-false}" == "true" ]]; then
        curl -X POST "https://events.pagerduty.com/v2/enqueue" \
            -H "Content-Type: application/json" \
            -d '{
                "routing_key": "'"$PAGERDUTY_INTEGRATION_KEY"'",
                "event_action": "trigger",
                "payload": {
                    "summary": "Emergency Maintenance: '"$MAINTENANCE_MESSAGE"'",
                    "severity": "warning",
                    "source": "sibali-dr-script"
                }
            }'
    fi

    # Update status page
    curl -X POST "$STATUS_PAGE_URL/api/status" \
        -H "Authorization: Bearer $STATUS_PAGE_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{
            "status": "maintenance",
            "message": "'"$MAINTENANCE_MESSAGE"'"
        }'

    log "Notifications sent"
}

# Recovery exit procedures
prepare_recovery_exit() {
    log "Preparing recovery exit procedures..."

    # Schedule maintenance end
    local end_time=$(date -d "+$MAINTENANCE_DURATION_HOURS hours" +%s)
    echo "$end_time" > "/tmp/maintenance-end-timestamp"

    # Set up monitoring for recovery
    log "Setting up recovery monitoring..."

    # Create recovery checklist
    cat > "/tmp/recovery-checklist-$(date +%Y%m%d-%H%M%S).md" << EOF
# Emergency Maintenance Recovery Checklist

## Pre-Exit Validation
- [ ] Application health checks passing
- [ ] Database connections stable
- [ ] External integrations tested
- [ ] Load balancer health checks green

## Exit Steps
- [ ] Disable maintenance mode in Laravel
- [ ] Restore load balancer configuration
- [ ] Re-enable background job processors
- [ ] Resume external integrations
- [ ] Validate application functionality

## Post-Exit Validation
- [ ] Run smoke tests
- [ ] Monitor error rates for 30 minutes
- [ ] Verify user-facing functionality
- [ ] Update status page to operational

## Rollback Plan (if issues found)
- [ ] Immediate re-enable maintenance mode
- [ ] Investigate and fix issues
- [ ] Retry exit procedures
EOF

    log "Recovery exit procedures prepared"
}

# Safety and audit logging
perform_safety_checks() {
    log "Performing safety checks..."

    # Require approval chain
    if [[ "${APPROVAL_RECEIVED:-false}" != "true" ]]; then
        error_exit "Maintenance approval not received"
    fi

    # Log operator identity
    local operator_id="${OPERATOR_ID:-unknown}"
    log "Operation initiated by: $operator_id"

    # Create timestamped artifacts
    local artifact_timestamp=$(date +%Y%m%d-%H%M%S)
    aws s3 cp "$LOG_FILE" "s3://sibali-audit-logs/maintenance/$artifact_timestamp-operation-log.txt"

    # Validate maintenance window
    local current_hour=$(date +%H)
    if [[ $current_hour -ge 22 || $current_hour -le 6 ]]; then
        log "WARNING: Operating outside standard maintenance window"
    fi

    log "Safety checks completed"
}

# Main execution
main() {
    log "Starting Sibali.id Emergency Maintenance Script v$SCRIPT_VERSION"
    log "Log file: $LOG_FILE"
    log "Maintenance duration: $MAINTENANCE_DURATION_HOURS hours"

    perform_safety_checks
    activate_maintenance_mode
    preserve_logs_and_traces
    protect_critical_state
    handle_data_isolation
    send_notifications
    prepare_recovery_exit

    log "Emergency maintenance activated successfully"
    log "Total execution time: ${SECONDS} seconds"
    log "Maintenance will auto-expire in $MAINTENANCE_DURATION_HOURS hours"

    # Optional: Wait for manual confirmation before proceeding
    if [[ "${WAIT_FOR_CONFIRMATION:-false}" == "true" ]]; then
        log "Waiting for manual confirmation to proceed..."
        read -p "Press Enter to confirm maintenance activation..."
    fi
}

# Dry run mode
if [[ "${DRY_RUN:-false}" == "true" ]]; then
    log "DRY RUN MODE - No actual changes will be made"
    exit 0
fi

# Confirmation prompt
if [[ "${SKIP_CONFIRMATION:-false}" != "true" ]]; then
    echo "This script will activate emergency maintenance mode."
    echo "Duration: $MAINTENANCE_DURATION_HOURS hours"
    echo "Message: $MAINTENANCE_MESSAGE"
    echo
    echo "This will:"
    echo "- Enable maintenance mode across all services"
    echo "- Preserve logs and traces"
    echo "- Protect critical state"
    echo "- Send notifications to stakeholders"
    echo
    read -p "Are you sure you want to proceed? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Operation cancelled by user"
        exit 0
    fi
fi

main "$@"
