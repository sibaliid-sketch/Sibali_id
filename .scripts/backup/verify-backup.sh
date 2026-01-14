#!/bin/bash

# Backup Verification Script for Sibali.id
# Purpose: Verifikasi integritas backup rutin untuk memastikan restorability
# Function: Verify checksums, test archives, dry-run restore checks

set -e

# Configuration
BACKUP_ROOT="/var/backups/sibali"
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-verify_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"
LOG_FILE="${BACKUP_ROOT}/logs/verify_backup.log"
REPORT_FILE="${BACKUP_ROOT}/reports/verification_$(date +%Y%m%d_%H%M%S).txt"
DASHBOARD_URL="${DASHBOARD_URL:-http://localhost:3000/api/backup-status}"

# Ensure directories exist
mkdir -p "$(dirname "$LOG_FILE")" "$(dirname "$REPORT_FILE")"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Report function
report() {
    echo "$*" >> "$REPORT_FILE"
    log "$*"
}

# Initialize report
cat > "$REPORT_FILE" << EOF
Backup Verification Report
==========================
Date: $(date)
Host: $(hostname)
EOF

VERIFICATION_PASSED=true

# Find all backups to verify
BACKUPS=$(find "$BACKUP_ROOT" -name "full_*" -o -name "incremental_*" | sort -r | head -10)

if [ -z "$BACKUPS" ]; then
    report "ERROR: No backups found to verify"
    VERIFICATION_PASSED=false
fi

for BACKUP_DIR in $BACKUPS; do
    report ""
    report "Verifying backup: $BACKUP_DIR"

    # Check manifest
    if [ ! -f "$BACKUP_DIR/manifest.txt" ]; then
        report "FAIL: Manifest missing"
        VERIFICATION_PASSED=false
        continue
    fi

    # Read manifest info
    BACKUP_TYPE=$(grep "Backup Type:" "$BACKUP_DIR/manifest.txt" | cut -d: -f2 | xargs)
    TIMESTAMP=$(grep "Timestamp:" "$BACKUP_DIR/manifest.txt" | cut -d: -f2 | xargs)

    report "Type: $BACKUP_TYPE"
    report "Timestamp: $TIMESTAMP"

    # Verify checksums
    if [ -f "$BACKUP_DIR/checksums.sha256" ]; then
        report "Checking checksums..."
        if (cd "$BACKUP_DIR" && sha256sum -c checksums.sha256 >/dev/null 2>&1); then
            report "PASS: Checksums verified"
        else
            report "FAIL: Checksum verification failed"
            VERIFICATION_PASSED=false
        fi
    else
        report "WARNING: No checksums file found"
    fi

    # Test archive integrity
    if [ -f "$BACKUP_DIR/database.sql.gz" ]; then
        report "Testing database archive..."
        if gunzip -t "$BACKUP_DIR/database.sql.gz" >/dev/null 2>&1; then
            report "PASS: Database archive integrity OK"
        else
            report "FAIL: Database archive corrupted"
            VERIFICATION_PASSED=false
        fi
    fi

    # Test file backup
    if [ -d "$BACKUP_DIR/files" ]; then
        report "Testing file backup..."
        FILE_COUNT=$(find "$BACKUP_DIR/files" -type f | wc -l)
        report "Files in backup: $FILE_COUNT"

        if [ "$FILE_COUNT" -gt 0 ]; then
            report "PASS: File backup contains files"
        else
            report "FAIL: File backup is empty"
            VERIFICATION_PASSED=false
        fi
    fi

    # Dry-run restore check (optional, resource intensive)
    if [ "${DRY_RUN_CHECK:-false}" = "true" ] && [ -f "$BACKUP_DIR/database.sql.gz" ]; then
        report "Performing dry-run restore check..."

        # Create temporary database
        TEMP_DB="verify_$(date +%s)"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE $TEMP_DB;" 2>/dev/null || true

        if [ $? -eq 0 ]; then
            # Import limited data for verification
            gunzip -c "$BACKUP_DIR/database.sql.gz" | head -1000 | mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$TEMP_DB" 2>/dev/null || true

            # Quick row count check
            ROW_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $TEMP_DB; SELECT SUM(TABLE_ROWS) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$TEMP_DB';" 2>/dev/null | tail -1)

            if [ "$ROW_COUNT" -gt 0 ] 2>/dev/null; then
                report "PASS: Dry-run restore successful, estimated rows: $ROW_COUNT"
            else
                report "FAIL: Dry-run restore failed"
                VERIFICATION_PASSED=false
            fi

            # Cleanup
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE $TEMP_DB;" 2>/dev/null || true
        else
            report "WARNING: Could not create temporary database for dry-run"
        fi
    fi

    # Check backup age
    BACKUP_AGE=$(( ($(date +%s) - $(stat -c %Y "$BACKUP_DIR" 2>/dev/null || date +%s)) / 86400 ))
    if [ "$BACKUP_AGE" -gt 7 ]; then
        report "WARNING: Backup is $BACKUP_AGE days old"
    fi

done

# Final status
report ""
if [ "$VERIFICATION_PASSED" = "true" ]; then
    report "OVERALL STATUS: PASSED"
    STATUS="PASSED"
else
    report "OVERALL STATUS: FAILED"
    STATUS="FAILED"
fi

# Send to dashboard (if configured)
if [ -n "$DASHBOARD_URL" ] && command -v curl >/dev/null 2>&1; then
    curl -X POST "$DASHBOARD_URL" \
         -H "Content-Type: application/json" \
         -d "{\"status\":\"$STATUS\",\"report\":\"$(cat "$REPORT_FILE" | base64 -w 0)\"}" \
         >/dev/null 2>&1 || true
fi

# Create ticket if failed (placeholder - integrate with ticketing system)
if [ "$VERIFICATION_PASSED" = "false" ]; then
    report "ACTION REQUIRED: Backup verification failed - please investigate"
    # TODO: Integrate with ticketing system (Jira, ServiceNow, etc.)
fi

log "Verification completed. Report: $REPORT_FILE"

exit 0
