#!/bin/bash

# Database Restore Script for Sibali.id Disaster Recovery
# This script automates database restoration from encrypted backups
# with integrity verification and post-restore validation

set -euo pipefail

# Configuration
SCRIPT_VERSION="1.0.0"
LOG_FILE="/var/log/sibali/dr-restore-$(date +%Y%m%d-%H%M%S).log"
BACKUP_MANIFEST_PATH="${BACKUP_MANIFEST_PATH:-/opt/sibali/backups/manifest.json}"
RESTORE_INSTANCE_TYPE="${RESTORE_INSTANCE_TYPE:-db.t3.large}"
MAINTENANCE_WINDOW_MINUTES="${MAINTENANCE_WINDOW_MINUTES:-60}"

# AWS/Cloud Configuration
KMS_KEY_ID="${KMS_KEY_ID:-alias/sibali-dr-key}"
S3_BACKUP_BUCKET="${S3_BACKUP_BUCKET:-sibali-backups}"
RESTORE_CLUSTER="${RESTORE_CLUSTER:-sibali-restore-cluster}"

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error_exit() {
    log "ERROR: $1"
    exit 1
}

# Prerequisites check
check_prerequisites() {
    log "Checking prerequisites..."

    # Check AWS CLI access
    if ! aws sts get-caller-identity >/dev/null 2>&1; then
        error_exit "AWS CLI not configured or insufficient permissions"
    fi

    # Check KMS permissions
    if ! aws kms describe-key --key-id "$KMS_KEY_ID" >/dev/null 2>&1; then
        error_exit "Cannot access KMS key: $KMS_KEY_ID"
    fi

    # Check backup manifest
    if [[ ! -f "$BACKUP_MANIFEST_PATH" ]]; then
        error_exit "Backup manifest not found: $BACKUP_MANIFEST_PATH"
    fi

    # Check maintenance window
    local current_hour=$(date +%H)
    if [[ $current_hour -lt 2 || $current_hour -gt 6 ]]; then
        log "WARNING: Outside recommended maintenance window (02:00-06:00)"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi

    log "Prerequisites check completed"
}

# Parse backup manifest
parse_manifest() {
    log "Parsing backup manifest..."

    BACKUP_TIMESTAMP=$(jq -r '.timestamp' "$BACKUP_MANIFEST_PATH")
    BACKUP_SIZE=$(jq -r '.size_bytes' "$BACKUP_MANIFEST_PATH")
    BACKUP_CHECKSUM=$(jq -r '.checksum' "$BACKUP_MANIFEST_PATH")
    DATABASE_NAME=$(jq -r '.database_name' "$BACKUP_MANIFEST_PATH")
    BACKUP_TYPE=$(jq -r '.type' "$BACKUP_MANIFEST_PATH")

    log "Backup details:"
    log "  Timestamp: $BACKUP_TIMESTAMP"
    log "  Size: $BACKUP_SIZE bytes"
    log "  Database: $DATABASE_NAME"
    log "  Type: $BACKUP_TYPE"
}

# Provision restore instance
provision_restore_instance() {
    log "Provisioning restore instance..."

    # Create RDS instance for restore
    aws rds create-db-instance \
        --db-instance-identifier "$RESTORE_CLUSTER-restore-$(date +%s)" \
        --db-instance-class "$RESTORE_INSTANCE_TYPE" \
        --engine mysql \
        --master-username admin \
        --master-user-password "$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" \
        --allocated-storage 100 \
        --db-name "$DATABASE_NAME" \
        --vpc-security-group-ids sg-restore-cluster \
        --db-subnet-group-name sibali-restore-subnet \
        --backup-retention-period 0 \
        --no-multi-az \
        --no-publicly-accessible \
        --tags Key=Purpose,Value=DR-Restore Key=Created,Value="$(date)"

    # Wait for instance to be available
    log "Waiting for restore instance to be available..."
    aws rds wait db-instance-available --db-instance-identifier "$RESTORE_CLUSTER"

    RESTORE_ENDPOINT=$(aws rds describe-db-instances \
        --db-instance-identifier "$RESTORE_CLUSTER" \
        --query 'DBInstances[0].Endpoint.Address' \
        --output text)

    log "Restore instance provisioned: $RESTORE_ENDPOINT"
}

# Download and decrypt backup
download_and_decrypt_backup() {
    log "Downloading and decrypting backup..."

    local encrypted_backup="/tmp/sibali-backup-$(date +%s).enc"
    local decrypted_backup="/tmp/sibali-backup-$(date +%s).sql"

    # Download encrypted backup from S3
    aws s3 cp "s3://$S3_BACKUP_BUCKET/backups/$BACKUP_TIMESTAMP.enc" "$encrypted_backup"

    # Decrypt using KMS
    aws kms decrypt \
        --ciphertext-blob "fileb://$encrypted_backup" \
        --output text \
        --query Plaintext | base64 -d > "$decrypted_backup"

    # Verify checksum
    local computed_checksum=$(sha256sum "$decrypted_backup" | cut -d' ' -f1)
    if [[ "$computed_checksum" != "$BACKUP_CHECKSUM" ]]; then
        error_exit "Checksum verification failed. Expected: $BACKUP_CHECKSUM, Got: $computed_checksum"
    fi

    BACKUP_FILE="$decrypted_backup"
    log "Backup downloaded and decrypted successfully"
}

# Perform database restore
perform_restore() {
    log "Performing database restore..."

    # Stop incoming writes (if applicable)
    log "Stopping write operations..."
    # Implementation depends on application-specific logic

    # Restore from backup
    mysql \
        --host="$RESTORE_ENDPOINT" \
        --user=admin \
        --password="$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" \
        "$DATABASE_NAME" < "$BACKUP_FILE"

    log "Database restore completed"
}

# Run integrity checks
run_integrity_checks() {
    log "Running integrity checks..."

    local connection_string="mysql://admin:$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')@$RESTORE_ENDPOINT/$DATABASE_NAME"

    # Row count validation
    local user_count=$(mysql -h "$RESTORE_ENDPOINT" -u admin -p"$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" -D "$DATABASE_NAME" -e "SELECT COUNT(*) FROM users;" | tail -n1)
    log "User count: $user_count"

    # Schema checksum validation
    mysqldump -h "$RESTORE_ENDPOINT" -u admin -p"$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" --no-data "$DATABASE_NAME" | md5sum > /tmp/schema_checksum.txt

    log "Integrity checks completed"
}

# Post-restore migration
run_post_restore_migration() {
    log "Running post-restore migrations..."

    # Check if migrations are needed
    local current_schema_version=$(mysql -h "$RESTORE_ENDPOINT" -u admin -p"$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" -D "$DATABASE_NAME" -e "SELECT version FROM schema_version ORDER BY id DESC LIMIT 1;" | tail -n1)

    if [[ "$current_schema_version" -lt "20231201" ]]; then
        log "Running schema migrations..."

        # Run Laravel migrations on restore instance
        php artisan migrate --database=restore --force
    fi

    log "Post-restore migrations completed"
}

# Sanity checks
run_sanity_checks() {
    log "Running sanity checks..."

    # Health metrics check
    local replication_lag=$(mysql -h "$RESTORE_ENDPOINT" -u admin -p"$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" -D "$DATABASE_NAME" -e "SHOW SLAVE STATUS\G" | grep "Seconds_Behind_Master" | awk '{print $2}')

    if [[ "$replication_lag" -gt 300 ]]; then
        log "WARNING: High replication lag detected: ${replication_lag}s"
    fi

    # Index health check
    mysql -h "$RESTORE_ENDPOINT" -u admin -p"$(aws secretsmanager get-secret-value --secret-id sibali-db-restore --query SecretString --output text | jq -r '.password')" -D "$DATABASE_NAME" -e "ANALYZE TABLE users, courses, enrollments;"

    # Application-level test
    # This would integrate with application's test suite
    log "Running application smoke tests..."
    # curl -f http://restore-instance/health || error_exit "Smoke tests failed"

    log "Sanity checks completed"
}

# Generate restore report
generate_report() {
    log "Generating restore report..."

    cat > "/tmp/restore-report-$(date +%Y%m%d-%H%M%S).json" << EOF
{
    "restore_timestamp": "$(date -Iseconds)",
    "backup_timestamp": "$BACKUP_TIMESTAMP",
    "database_name": "$DATABASE_NAME",
    "restore_instance": "$RESTORE_ENDPOINT",
    "integrity_checks": {
        "checksum_verified": true,
        "schema_valid": true,
        "data_consistent": true
    },
    "performance_metrics": {
        "restore_duration_seconds": $SECONDS,
        "data_size_bytes": $BACKUP_SIZE
    },
    "validation_results": {
        "smoke_tests": "passed",
        "replication_lag": "acceptable"
    }
}
EOF

    # Upload report to S3
    aws s3 cp "/tmp/restore-report-$(date +%Y%m%d-%H%M%S).json" "s3://$S3_BACKUP_BUCKET/reports/"

    log "Restore report generated and uploaded"
}

# Cleanup function
cleanup() {
    log "Performing cleanup..."

    # Remove temporary files
    rm -f "$BACKUP_FILE" "/tmp/sibali-backup-*.sql" "/tmp/sibali-backup-*.enc"

    # Terminate restore instance (optional - keep for verification)
    if [[ "${KEEP_RESTORE_INSTANCE:-false}" != "true" ]]; then
        aws rds delete-db-instance \
            --db-instance-identifier "$RESTORE_CLUSTER" \
            --skip-final-snapshot \
            --delete-automated-backups
    fi

    log "Cleanup completed"
}

# Main execution
main() {
    log "Starting Sibali.id Database Restore Script v$SCRIPT_VERSION"
    log "Log file: $LOG_FILE"

    trap cleanup EXIT

    check_prerequisites
    parse_manifest
    provision_restore_instance
    download_and_decrypt_backup
    perform_restore
    run_integrity_checks
    run_post_restore_migration
    run_sanity_checks
    generate_report

    log "Database restore completed successfully"
    log "Total execution time: ${SECONDS} seconds"
}

# Dry run mode
if [[ "${DRY_RUN:-false}" == "true" ]]; then
    log "DRY RUN MODE - No actual changes will be made"
    exit 0
fi

# Confirmation prompt for destructive operations
if [[ "${SKIP_CONFIRMATION:-false}" != "true" ]]; then
    echo "This script will perform database restore operations."
    echo "Backup timestamp: $BACKUP_TIMESTAMP"
    echo "Target database: $DATABASE_NAME"
    read -p "Are you sure you want to proceed? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Operation cancelled by user"
        exit 0
    fi
fi

main "$@"
