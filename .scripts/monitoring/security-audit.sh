#!/bin/bash

# Security Audit Script for Sibali.id
# Purpose: Runner audit keamanan berkala: vuln scans & config drift checks
# Function: Scan for vulnerabilities and configuration issues

set -e

# Configuration
LOG_DIR="/var/log/sibali"
REPORT_DIR="/var/reports/security"
SCAN_DIR="/var/www/html"
TICKET_API="${TICKET_API:-}"
SSO_API="${SSO_API:-}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_FILE="$REPORT_DIR/security_audit_$TIMESTAMP.sarif"
CSV_REPORT="$REPORT_DIR/security_audit_$TIMESTAMP.csv"

# Ensure directories exist
mkdir -p "$LOG_DIR" "$REPORT_DIR"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOG_DIR/security_audit.log"
}

# Initialize SARIF report
init_sarif_report() {
    cat > "$REPORT_FILE" << EOF
{
  "version": "2.1.0",
  "\$schema": "https://raw.githubusercontent.com/oasis-tcs/sarif-spec/master/Schemata/sarif-schema-2.1.0.json",
  "runs": [{
    "tool": {
      "driver": {
        "name": "Sibali Security Audit",
        "version": "1.0.0",
        "informationUri": "https://sibali.id/security"
      }
    },
    "results": []
  }]
}
EOF

    # Initialize CSV report
    cat > "$CSV_REPORT" << EOF
Severity,Rule,Description,Location,Owner,Status
EOF
}

# Add finding to reports
add_finding() {
    local severity="$1"
    local rule="$2"
    local description="$3"
    local location="$4"
    local owner="$5"

    # Add to SARIF
    jq --arg severity "$severity" \
       --arg rule "$rule" \
       --arg desc "$description" \
       --arg loc "$location" \
       '.runs[0].results += [{
         "ruleId": $rule,
         "level": $severity,
         "message": {"text": $desc},
         "locations": [{"physicalLocation": {"artifactLocation": {"uri": $loc}}}]
       }]' "$REPORT_FILE" > "${REPORT_FILE}.tmp" && mv "${REPORT_FILE}.tmp" "$REPORT_FILE"

    # Add to CSV
    echo "$severity,$rule,$description,$location,$owner,OPEN" >> "$CSV_REPORT"
}

# Scan for vulnerable dependencies
scan_dependencies() {
    log "Scanning PHP dependencies..."

    if [ -f "$SCAN_DIR/composer.lock" ]; then
        # Use Composer audit
        if composer audit --format=json 2>/dev/null > /tmp/composer_audit.json; then
            VULN_COUNT=$(jq '.advisories | length' /tmp/composer_audit.json 2>/dev/null || echo "0")

            if [ "$VULN_COUNT" -gt 0 ]; then
                jq -r '.advisories[] | "\(.package) \(.advisoryId) \(.title)"' /tmp/composer_audit.json 2>/dev/null | while read -r vuln; do
                    add_finding "error" "composer-vuln" "Vulnerable dependency: $vuln" "composer.lock" "platform-team"
                done
            fi
        fi
    fi

    # Scan Node.js if present
    if [ -f "$SCAN_DIR/package-lock.json" ]; then
        log "Scanning Node.js dependencies..."
        if command -v npm >/dev/null 2>&1; then
            npm audit --audit-level=moderate --json 2>/dev/null > /tmp/npm_audit.json || true
            VULN_COUNT=$(jq '.metadata.vulnerabilities.total' /tmp/npm_audit.json 2>/dev/null || echo "0")

            if [ "$VULN_COUNT" -gt 0 ]; then
                add_finding "error" "npm-vuln" "Node.js vulnerabilities found: $VULN_COUNT" "package-lock.json" "frontend-team"
            fi
        fi
    fi
}

# Scan container images (if Docker available)
scan_containers() {
    log "Scanning container images..."

    if command -v docker >/dev/null 2>&1; then
        # Scan running containers
        docker ps --format "table {{.Image}}\t{{.Names}}" | tail -n +2 | while read -r image name; do
            log "Scanning container: $name ($image)"

            # Use Trivy if available
            if command -v trivy >/dev/null 2>&1; then
                trivy image --format json "$image" 2>/dev/null > /tmp/trivy_scan.json || true

                CRITICAL_VULN=$(jq '.Results[].Vulnerabilities[] | select(.Severity == "CRITICAL") | .VulnerabilityID' /tmp/trivy_scan.json 2>/dev/null | wc -l)
                HIGH_VULN=$(jq '.Results[].Vulnerabilities[] | select(.Severity == "HIGH") | .VulnerabilityID' /tmp/trivy_scan.json 2>/dev/null | wc -l)

                if [ "$CRITICAL_VULN" -gt 0 ]; then
                    add_finding "error" "container-critical-vuln" "Critical vulnerabilities in container $name: $CRITICAL_VULN" "$image" "platform-team"
                fi

                if [ "$HIGH_VULN" -gt 0 ]; then
                    add_finding "warning" "container-high-vuln" "High vulnerabilities in container $name: $HIGH_VULN" "$image" "platform-team"
                fi
            fi
        done
    fi
}

# Check for stale credentials
check_stale_credentials() {
    log "Checking for stale credentials..."

    # Search for potential credential patterns
    find "$SCAN_DIR" -name "*.php" -o -name "*.env*" -o -name "*.config" | while read -r file; do
        # Skip binary files and large files
        if [ -f "$file" ] && [ $(stat -f%z "$file" 2>/dev/null || stat -c%s "$file") -lt 10485760 ]; then
            # Look for API keys, passwords, secrets
            if grep -q -E "(api_key|apikey|secret|password|token).*['\"][^'\"]{10,}['\"]" "$file" 2>/dev/null; then
                add_finding "error" "hardcoded-credentials" "Potential hardcoded credentials found" "$file" "security-team"
            fi

            # Check for old SSH keys or certificates
            if grep -q "BEGIN.*PRIVATE KEY" "$file" 2>/dev/null; then
                # Check if certificate is expired or close to expiry
                if openssl x509 -checkend 2592000 -noout "$file" 2>/dev/null; then
                    add_finding "warning" "expiring-certificate" "Certificate expires within 30 days" "$file" "platform-team"
                fi
            fi
        fi
    done
}

# Check configuration drift
check_config_drift() {
    log "Checking configuration drift..."

    # Check if .env matches .env.example structure
    if [ -f "$SCAN_DIR/.env" ] && [ -f "$SCAN_DIR/.env.example" ]; then
        MISSING_VARS=$(comm -23 <(grep '^[^#]*=' .env.example | sort) <(grep '^[^#]*=' .env | sort) | wc -l)

        if [ "$MISSING_VARS" -gt 0 ]; then
            add_finding "warning" "config-drift" "Environment variables missing from .env: $MISSING_VARS" ".env" "platform-team"
        fi
    fi

    # Check file permissions
    find "$SCAN_DIR" -name "*.env*" -exec ls -la {} \; | while read -r line; do
        PERMS=$(echo "$line" | awk '{print $1}')
        FILE=$(echo "$line" | awk '{print $NF}')

        # Check if .env files are world-readable
        if [[ "$PERMS" =~ ^-..[rwx] ]]; then
            add_finding "error" "insecure-file-permissions" "File has insecure permissions: $PERMS" "$FILE" "platform-team"
        fi
    done
}

# Create tickets for critical findings
create_tickets() {
    if [ -n "$TICKET_API" ]; then
        log "Creating tickets for critical findings..."

        # Process critical findings
        jq -r '.runs[0].results[] | select(.level == "error") | @json' "$REPORT_FILE" | while read -r finding; do
            TITLE=$(echo "$finding" | jq -r '.message.text')
            DESCRIPTION=$(echo "$finding" | jq -r '.ruleId + ": " + .message.text')
            ASSIGNEE=$(echo "$finding" | jq -r '.properties.owner // "security-team"')

            # Create ticket via API
            curl -X POST "$TICKET_API/tickets" \
                 -H "Content-Type: application/json" \
                 -d "{\"title\":\"Security Finding: $TITLE\",\"description\":\"$DESCRIPTION\",\"assignee\":\"$ASSIGNEE\",\"priority\":\"high\"}" \
                 >/dev/null 2>&1 || true
        done
    fi
}

# Main execution
log "Starting security audit"

init_sarif_report
scan_dependencies
scan_containers
check_stale_credentials
check_config_drift
create_tickets

log "Security audit completed. SARIF Report: $REPORT_FILE, CSV Report: $CSV_REPORT"

# Archive old reports (keep 90 days)
find "$REPORT_DIR" -name "security_audit_*.sarif" -o -name "security_audit_*.csv" -mtime +90 -delete 2>/dev/null || true

exit 0
