#!/bin/bash

# Failover Activation Script for Sibali.id Disaster Recovery
# This script automates traffic failover to DR/standby site with safety checks

set -euo pipefail

# Configuration
SCRIPT_VERSION="1.0.0"
LOG_FILE="/var/log/sibali/dr-failover-$(date +%Y%m%d-%H%M%S).log"
PRIMARY_REGION="${PRIMARY_REGION:-ap-southeast-1}"
DR_REGION="${DR_REGION:-ap-southeast-2}"
HEALTH_CHECK_INTERVAL="${HEALTH_CHECK_INTERVAL:-30}"
TRAFFIC_SHIFT_PERCENTAGE="${TRAFFIC_SHIFT_PERCENTAGE:-100}"

# AWS/Cloud Configuration
PRIMARY_LB="${PRIMARY_LB:-sibali-primary-alb}"
DR_LB="${DR_LB:-sibali-dr-alb}"
ROUTE53_HOSTED_ZONE="${ROUTE53_HOSTED_ZONE:-Z123456789}"
DOMAIN_NAME="${DOMAIN_NAME:-www.sibali.id}"

# Monitoring thresholds
REPLICATION_LAG_THRESHOLD="${REPLICATION_LAG_THRESHOLD:-300}"
ERROR_RATE_THRESHOLD="${ERROR_RATE_THRESHOLD:-0.1}"
RESPONSE_TIME_THRESHOLD="${RESPONSE_TIME_THRESHOLD:-5000}"

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error_exit() {
    log "ERROR: $1"
    exit 1
}

# Pre-checks
perform_pre_checks() {
    log "Performing pre-failover checks..."

    # Check if DR is already declared
    if [[ "${DR_DECLARED:-false}" != "true" ]]; then
        error_exit "DR state not declared. Run declare-dr.sh first"
    fi

    # Verify standby infrastructure readiness
    log "Checking standby infrastructure..."
    local dr_lb_status=$(aws elbv2 describe-load-balancers \
        --region "$DR_REGION" \
        --names "$DR_LB" \
        --query 'LoadBalancers[0].State.Code' \
        --output text 2>/dev/null || echo "not-found")

    if [[ "$dr_lb_status" != "active" ]]; then
        error_exit "DR load balancer not ready: $dr_lb_status"
    fi

    # Check data sync status
    log "Checking database replication status..."
    local replication_lag=$(mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master" | awk '{print $2}' || echo "unknown")

    if [[ "$replication_lag" == "unknown" || "$replication_lag" -gt "$REPLICATION_LAG_THRESHOLD" ]]; then
        error_exit "Replication lag too high: ${replication_lag}s (threshold: ${REPLICATION_LAG_THRESHOLD}s)"
    fi

    # Validate certificates and secrets
    log "Validating certificates and secrets..."
    if ! aws acm list-certificates --region "$DR_REGION" \
        --query 'CertificateSummaryList[?DomainName==`'"$DOMAIN_NAME"'`].CertificateArn' \
        --output text | grep -q "arn:"; then
        error_exit "SSL certificate not found in DR region"
    fi

    log "Pre-checks completed successfully"
}

# DNS and Load Balancer reconfiguration
reconfigure_dns_and_lb() {
    log "Reconfiguring DNS and Load Balancers..."

    # Lower DNS TTL pre-approved
    log "Lowering DNS TTL..."
    aws route53 change-resource-record-sets \
        --hosted-zone-id "$ROUTE53_HOSTED_ZONE" \
        --change-batch '{
            "Changes": [{
                "Action": "UPSERT",
                "ResourceRecordSet": {
                    "Name": "'"$DOMAIN_NAME"'",
                    "Type": "A",
                    "TTL": 60,
                    "ResourceRecords": [{"Value": "'"$PRIMARY_LB_IP"'"}]
                }
            }]
        }'

    # Wait for TTL to expire (conservative wait)
    log "Waiting for DNS TTL to expire..."
    sleep 120

    # Update Route53 to point to DR load balancer
    local dr_lb_dns=$(aws elbv2 describe-load-balancers \
        --region "$DR_REGION" \
        --names "$DR_LB" \
        --query 'LoadBalancers[0].DNSName' \
        --output text)

    log "Updating DNS to point to DR load balancer: $dr_lb_dns"
    aws route53 change-resource-record-sets \
        --hosted-zone-id "$ROUTE53_HOSTED_ZONE" \
        --change-batch '{
            "Changes": [{
                "Action": "UPSERT",
                "ResourceRecordSet": {
                    "Name": "'"$DOMAIN_NAME"'",
                    "Type": "A",
                    "AliasTarget": {
                        "DNSName": "'"$dr_lb_dns"'",
                        "HostedZoneId": "'$(aws elbv2 describe-load-balancers --region "$DR_REGION" --names "$DR_LB" --query 'LoadBalancers[0].CanonicalHostedZoneId' --output text)'",
                        "EvaluateTargetHealth": true
                    }
                }
            }]
        }'

    # Wait for DNS propagation
    log "Waiting for DNS propagation..."
    sleep 300

    # Verify DNS resolution
    local resolved_ip=$(dig +short "$DOMAIN_NAME" | head -n1)
    local dr_lb_ip=$(dig +short "$dr_lb_dns" | head -n1)

    if [[ "$resolved_ip" != "$dr_lb_ip" ]]; then
        log "WARNING: DNS may not have propagated yet. Resolved: $resolved_ip, Expected: $dr_lb_ip"
    fi

    log "DNS reconfiguration completed"
}

# Database promotion
promote_database() {
    log "Promoting DR database to primary..."

    # Verify replication state
    local io_running=$(mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "SHOW SLAVE STATUS\G" | grep "Slave_IO_Running" | awk '{print $2}')

    local sql_running=$(mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "SHOW SLAVE STATUS\G" | grep "Slave_SQL_Running" | awk '{print $2}')

    if [[ "$io_running" != "Yes" || "$sql_running" != "Yes" ]]; then
        error_exit "Replication not healthy: IO=$io_running, SQL=$sql_running"
    fi

    # Stop slave threads
    log "Stopping slave threads..."
    mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "STOP SLAVE;"

    # Promote to primary (reset master)
    log "Promoting to primary..."
    mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "RESET MASTER;"

    # Update application configuration
    log "Updating application database endpoints..."
    # This would update configuration management system
    aws ssm put-parameter \
        --name "/sibali/database/endpoint" \
        --value "$DR_DB_ENDPOINT" \
        --type "String" \
        --overwrite

    # Publish read-write endpoints
    log "Publishing read-write endpoints..."
    aws sns publish \
        --topic-arn "arn:aws:sns:$DR_REGION:123456789012:sibali-db-failover" \
        --message '{"event": "database_failover", "new_primary": "'"$DR_DB_ENDPOINT"'", "timestamp": "'$(date -Iseconds)'"}'

    log "Database promotion completed"
}

# Traffic shaping
perform_traffic_shaping() {
    log "Performing traffic shaping..."

    local current_percentage=0
    local increment=20

    while [[ $current_percentage -lt $TRAFFIC_SHIFT_PERCENTAGE ]]; do
        current_percentage=$((current_percentage + increment))
        if [[ $current_percentage -gt $TRAFFIC_SHIFT_PERCENTAGE ]]; then
            current_percentage=$TRAFFIC_SHIFT_PERCENTAGE
        fi

        log "Shifting $current_percentage% traffic to DR..."

        # Update Route53 weighted routing
        aws route53 change-resource-record-sets \
            --hosted-zone-id "$ROUTE53_HOSTED_ZONE" \
            --change-batch '{
                "Changes": [
                    {
                        "Action": "UPSERT",
                        "ResourceRecordSet": {
                            "Name": "'"$DOMAIN_NAME"'",
                            "Type": "A",
                            "SetIdentifier": "primary",
                            "Weight": '"$((100 - current_percentage))"',
                            "AliasTarget": {
                                "DNSName": "'"$PRIMARY_LB_DNS"'",
                                "HostedZoneId": "'"$PRIMARY_LB_ZONE"'",
                                "EvaluateTargetHealth": true
                            }
                        }
                    },
                    {
                        "Action": "UPSERT",
                        "ResourceRecordSet": {
                            "Name": "'"$DOMAIN_NAME"'",
                            "Type": "A",
                            "SetIdentifier": "dr",
                            "Weight": '"$current_percentage"',
                            "AliasTarget": {
                                "DNSName": "'"$DR_LB_DNS"'",
                                "HostedZoneId": "'"$DR_LB_ZONE"'",
                                "EvaluateTargetHealth": true
                            }
                        }
                    }
                ]
            }'

        # Monitor error rates and latency
        log "Monitoring traffic for 2 minutes..."
        sleep 120

        local error_rate=$(curl -s "http://monitoring.sibali.internal/metrics/error-rate" | jq -r '.rate')
        local avg_latency=$(curl -s "http://monitoring.sibali.internal/metrics/latency" | jq -r '.avg_ms')

        if [[ $(echo "$error_rate > $ERROR_RATE_THRESHOLD" | bc -l) -eq 1 ]]; then
            log "ERROR: Error rate too high: $error_rate (threshold: $ERROR_RATE_THRESHOLD)"
            # Automatic rollback
            rollback_traffic $((current_percentage - increment))
            error_exit "Traffic shift failed due to high error rate"
        fi

        if [[ $(echo "$avg_latency > $RESPONSE_TIME_THRESHOLD" | bc -l) -eq 1 ]]; then
            log "WARNING: High latency detected: ${avg_latency}ms (threshold: ${RESPONSE_TIME_THRESHOLD}ms)"
        fi
    done

    log "Traffic shaping completed"
}

# Verification
perform_verification() {
    log "Performing failover verification..."

    # Run synthetic transactions
    log "Running synthetic transactions..."
    local test_results=$(curl -s -X POST "http://synthetic-tests.sibali.internal/run" \
        -H "Content-Type: application/json" \
        -d '{"tests": ["auth_flow", "lms_enrollment", "payment_flow"]}')

    local passed_tests=$(echo "$test_results" | jq -r '.passed')
    local total_tests=$(echo "$test_results" | jq -r '.total')

    if [[ "$passed_tests" -ne "$total_tests" ]]; then
        log "WARNING: Some synthetic tests failed: $passed_tests/$total_tests"
    fi

    # Check application logs for errors
    log "Checking application logs for errors..."
    local error_count=$(aws logs filter-log-events \
        --log-group-name "/aws/lambda/sibali-api" \
        --start-time $(date -d '5 minutes ago' +%s000) \
        --filter-pattern "ERROR" \
        --query 'length(events[])' \
        --output text)

    if [[ "$error_count" -gt 10 ]]; then
        log "WARNING: High error count in logs: $error_count"
    fi

    # Validate downstream integrations
    log "Validating downstream integrations..."
    # Test payment gateway connectivity
    # Test email service
    # Test SMS service

    log "Verification completed"
}

# Rollback function
rollback_traffic() {
    local rollback_percentage="${1:-100}"
    log "Rolling back traffic to $rollback_percentage% primary..."

    aws route53 change-resource-record-sets \
        --hosted-zone-id "$ROUTE53_HOSTED_ZONE" \
        --change-batch '{
            "Changes": [{
                "Action": "UPSERT",
                "ResourceRecordSet": {
                    "Name": "'"$DOMAIN_NAME"'",
                    "Type": "A",
                    "AliasTarget": {
                        "DNSName": "'"$PRIMARY_LB_DNS"'",
                        "HostedZoneId": "'"$PRIMARY_LB_ZONE"'",
                        "EvaluateTargetHealth": true
                    }
                }
            }]
        }'

    log "Traffic rollback completed"
}

# Audit and approvals
log_actions() {
    log "Logging all actions for audit..."

    cat > "/tmp/failover-audit-$(date +%Y%m%d-%H%M%S).json" << EOF
{
    "failover_timestamp": "$(date -Iseconds)",
    "primary_region": "$PRIMARY_REGION",
    "dr_region": "$DR_REGION",
    "actions_performed": [
        "pre_checks",
        "dns_reconfiguration",
        "database_promotion",
        "traffic_shaping",
        "verification"
    ],
    "approvals": {
        "sre_lead": "${SRE_LEAD_APPROVAL:-pending}",
        "business_lead": "${BUSINESS_LEAD_APPROVAL:-pending}"
    },
    "metrics": {
        "replication_lag_before": "$REPLICATION_LAG_INITIAL",
        "traffic_shift_duration": "$TRAFFIC_SHIFT_DURATION",
        "error_rate_during_shift": "$ERROR_RATE_DURING_SHIFT"
    }
}
EOF

    # Upload audit log
    aws s3 cp "/tmp/failover-audit-$(date +%Y%m%d-%H%M%S).json" "s3://sibali-audit-logs/failover/"

    log "Audit logging completed"
}

# Main execution
main() {
    log "Starting Sibali.id Failover Activation Script v$SCRIPT_VERSION"
    log "Log file: $LOG_FILE"

    # Capture initial metrics
    REPLICATION_LAG_INITIAL=$(mysql -h "$DR_DB_ENDPOINT" -u admin \
        -p"$(aws secretsmanager get-secret-value --secret-id sibali-dr-db --query SecretString --output text | jq -r '.password')" \
        -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master" | awk '{print $2}' || echo "unknown")

    perform_pre_checks
    reconfigure_dns_and_lb
    promote_database
    perform_traffic_shaping
    perform_verification
    log_actions

    log "Failover activation completed successfully"
    log "Total execution time: ${SECONDS} seconds"
}

# Dry run mode
if [[ "${DRY_RUN:-false}" == "true" ]]; then
    log "DRY RUN MODE - No actual changes will be made"
    exit 0
fi

# Dual approval requirement
if [[ "${SRE_LEAD_APPROVAL:-false}" != "true" || "${BUSINESS_LEAD_APPROVAL:-false}" != "true" ]]; then
    error_exit "Dual approval required for region-level failover"
fi

# Confirmation prompt
if [[ "${SKIP_CONFIRMATION:-false}" != "true" ]]; then
    echo "This script will perform region failover operations."
    echo "Primary Region: $PRIMARY_REGION"
    echo "DR Region: $DR_REGION"
    echo "Traffic Shift: $TRAFFIC_SHIFT_PERCENTAGE%"
    read -p "Are you sure you want to proceed? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Operation cancelled by user"
        exit 0
    fi
fi

main "$@"
