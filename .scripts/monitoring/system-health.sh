#!/bin/bash

# System Health Monitoring Script for Sibali.id
# Pengumpulan ringkasan kesehatan sistem untuk laporan harian
# Kirim metrik ringkasan harian ke tim operasi & leadership; early-warning untuk trend negatif

set -euo pipefail

# Configuration
LOG_FILE="/var/log/sibali-health.log"
REPORT_FILE="/var/log/daily_health_report_$(date +%Y%m%d).txt"
EMAIL_RECIPIENTS="ops@sibali.id leadership@sibali.id"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"

# Metrics: disk usage, memory, CPU, service up/down, last restart times
THRESHOLDS_DISK=90
THRESHOLDS_MEMORY=85
THRESHOLDS_CPU=80

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Initialize report
init_report() {
    {
        echo "Daily System Health Report - $(date)"
        echo "====================================="
        echo ""
    } > "$REPORT_FILE"
}

# Add to report
report() {
    echo "$*" >> "$REPORT_FILE"
    log "$*"
}

# Check disk usage
check_disk_usage() {
    report "Disk Usage:"
    df -h | awk 'NR>1 {print "  " $1 ": " $5 " used (" $4 " free)"}' >> "$REPORT_FILE"

    # Check for high usage
    local high_usage
    high_usage=$(df -h | awk 'NR>1 && int($5) > '$THRESHOLDS_DISK' {print $1 ": " $5}')
    if [[ -n "$high_usage" ]]; then
        report "⚠️  WARNING: High disk usage detected:"
        echo "$high_usage" >> "$REPORT_FILE"
    fi
}

# Check memory usage
check_memory_usage() {
    report ""
    report "Memory Usage:"

    if command -v free >/dev/null 2>&1; then
        free -h | awk 'NR==2 {print "  Total: " $2 ", Used: " $3 " (" int($3/$2*100) "%)"}' >> "$REPORT_FILE"

        local mem_percent
        mem_percent=$(free | awk 'NR==2 {printf "%.0f", $3/$2 * 100.0}')
        if [[ $mem_percent -gt $THRESHOLDS_MEMORY ]]; then
            report "⚠️  WARNING: High memory usage: ${mem_percent}%"
        fi
    else
        report "  free command not available"
    fi
}

# Check CPU usage
check_cpu_usage() {
    report ""
    report "CPU Usage:"

    # Get CPU usage over 1 minute
    local cpu_load
    cpu_load=$(uptime | awk -F'load average:' '{ print $2 }' | awk '{print $1}' | sed 's/,//')

    if [[ -n "$cpu_load" ]]; then
        report "  Load Average (1min): $cpu_load"

        # Get number of CPU cores
        local cpu_cores
        cpu_cores=$(nproc 2>/dev/null || echo "1")

        # Calculate usage percentage (rough estimate)
        local cpu_percent
        cpu_percent=$(echo "scale=2; $cpu_load * 100 / $cpu_cores" | bc 2>/dev/null || echo "0")

        if [[ $(echo "$cpu_percent > $THRESHOLDS_CPU" | bc 2>/dev/null) -eq 1 ]]; then
            report "⚠️  WARNING: High CPU usage: ${cpu_percent}%"
        fi
    else
        report "  Unable to determine CPU load"
    fi
}

# Check service status
check_services() {
    report ""
    report "Service Status:"

    # Check critical services
    local services=("nginx" "mysql" "php8.1-fpm" "redis-server")

    for service in "${services[@]}"; do
        if systemctl is-active --quiet "$service" 2>/dev/null; then
            report "  ✓ $service: RUNNING"
        else
            report "  ✗ $service: NOT RUNNING"
        fi
    done

    # Check Laravel queue worker (if using supervisor)
    if pgrep -f "artisan queue:work" >/dev/null; then
        report "  ✓ Laravel Queue Worker: RUNNING"
    else
        report "  ⚠️  Laravel Queue Worker: NOT RUNNING"
    fi
}

# Check last restart times
check_restart_times() {
    report ""
    report "Last Service Restart Times:"

    local services=("nginx" "mysql" "php8.1-fpm" "redis-server")

    for service in "${services[@]}"; do
        if command -v systemctl >/dev/null 2>&1; then
            local restart_time
            restart_time=$(systemctl show "$service" -p ExecMainStartTimestamp | cut -d'=' -f2)
            if [[ -n "$restart_time" ]]; then
                report "  $service: $restart_time"
            else
                report "  $service: Unable to determine"
            fi
        else
            report "  $service: systemctl not available"
        fi
    done
}

# Send email report
send_email_report() {
    if command -v mail >/dev/null 2>&1; then
        mail -s "Daily System Health Report - $(date +%Y-%m-%d)" "$EMAIL_RECIPIENTS" < "$REPORT_FILE"
        log "Email report sent to $EMAIL_RECIPIENTS"
    else
        log "mail command not available, skipping email"
    fi
}

# Send Slack notification
send_slack_notification() {
    if [[ -n "$SLACK_WEBHOOK" ]]; then
        local payload
        payload=$(jq -n --arg text "$(cat "$REPORT_FILE")" '{text: $text}')

        curl -X POST -H 'Content-type: application/json' --data "$payload" "$SLACK_WEBHOOK"
        log "Slack notification sent"
    fi
}

# Archive daily snapshot for capacity planning
archive_report() {
    local archive_dir="/var/log/health_archive"
    mkdir -p "$archive_dir"
    cp "$REPORT_FILE" "$archive_dir/"
    log "Report archived to $archive_dir"
}

# Main execution
main() {
    log "Starting daily system health check"

    init_report

    check_disk_usage
    check_memory_usage
    check_cpu_usage
    check_services
    check_restart_times

    report ""
    report "Report generated at $(date)"

    # Send notifications
    send_email_report
    send_slack_notification

    # Archive
    archive_report

    log "Daily system health check completed"
}

main "$@"
