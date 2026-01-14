#!/bin/bash

# System Health Monitoring Script for Sibali.id
# Purpose: Pengumpulan ringkasan kesehatan sistem untuk laporan harian
# Function: Collect system metrics and generate reports

set -e

# Configuration
LOG_DIR="/var/log/sibali"
REPORT_DIR="/var/reports/health"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"
EMAIL_RECIPIENT="${EMAIL_RECIPIENT:-admin@sibali.id}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_FILE="$REPORT_DIR/health_report_$TIMESTAMP.json"
SUMMARY_FILE="$REPORT_DIR/health_summary_$TIMESTAMP.txt"

# Ensure directories exist
mkdir -p "$LOG_DIR" "$REPORT_DIR"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOG_DIR/health_monitor.log"
}

# Collect system metrics
collect_metrics() {
    # CPU Usage
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')

    # Memory Usage
    MEM_TOTAL=$(free -m | awk 'NR==2{printf "%.2f", $2/1024}')
    MEM_USED=$(free -m | awk 'NR==2{printf "%.2f", $3/1024}')
    MEM_FREE=$(free -m | awk 'NR==2{printf "%.2f", $4/1024}')
    MEM_USAGE_PERCENT=$(free | awk 'NR==2{printf "%.2f", $3*100/$2}')

    # Disk Usage
    DISK_ROOT=$(df -BG / | tail -1 | awk '{print $5}' | sed 's/%//')
    DISK_VAR=$(df -BG /var | tail -1 | awk '{print $5}' | sed 's/%//')
    DISK_BACKUP=$(df -BG /var/backups 2>/dev/null | tail -1 | awk '{print $5}' | sed 's/%//' || echo "N/A")

    # Load Average
    LOAD_1=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f1 | xargs)
    LOAD_5=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f2 | xargs)
    LOAD_15=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f3 | xargs)

    # Services status
    NGINX_STATUS=$(systemctl is-active nginx 2>/dev/null || echo "unknown")
    MYSQL_STATUS=$(systemctl is-active mysql 2>/dev/null || systemctl is-active mariadb 2>/dev/null || echo "unknown")
    PHP_FPM_STATUS=$(systemctl is-active php8.2-fpm 2>/dev/null || systemctl is-active php-fpm 2>/dev/null || echo "unknown")
    REDIS_STATUS=$(systemctl is-active redis-server 2>/dev/null || echo "unknown")

    # Service restart times
    NGINX_RESTART=$(systemctl show nginx -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || echo "unknown")
    MYSQL_RESTART=$(systemctl show mysql -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || systemctl show mariadb -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || echo "unknown")
    PHP_RESTART=$(systemctl show php8.2-fpm -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || systemctl show php-fpm -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || echo "unknown")
    REDIS_RESTART=$(systemctl show redis-server -p ExecMainStartTimestamp 2>/dev/null | cut -d= -f2 || echo "unknown")

    # Network connections
    ACTIVE_CONNECTIONS=$(netstat -tun | grep ESTABLISHED | wc -l)

    # Application specific metrics
    QUEUE_SIZE=$(php artisan queue:status 2>/dev/null | grep -oP '(?<=size: )\d+' || echo "unknown")
    CACHE_HITS=$(redis-cli info stats 2>/dev/null | grep keyspace_hits | cut -d: -f2 | xargs || echo "unknown")
    CACHE_MISSES=$(redis-cli info stats 2>/dev/null | grep keyspace_misses | cut -d: -f2 | xargs || echo "unknown")

    # Create JSON report
    cat > "$REPORT_FILE" << EOF
{
  "timestamp": "$TIMESTAMP",
  "hostname": "$(hostname)",
  "system": {
    "cpu_usage_percent": $CPU_USAGE,
    "memory": {
      "total_gb": $MEM_TOTAL,
      "used_gb": $MEM_USED,
      "free_gb": $MEM_FREE,
      "usage_percent": $MEM_USAGE_PERCENT
    },
    "disk": {
      "root_usage_percent": $DISK_ROOT,
      "var_usage_percent": $DISK_VAR,
      "backup_usage_percent": "$DISK_BACKUP"
    },
    "load_average": {
      "1min": $LOAD_1,
      "5min": $LOAD_5,
      "15min": $LOAD_15
    }
  },
  "services": {
    "nginx": {
      "status": "$NGINX_STATUS",
      "last_restart": "$NGINX_RESTART"
    },
    "mysql": {
      "status": "$MYSQL_STATUS",
      "last_restart": "$MYSQL_RESTART"
    },
    "php_fpm": {
      "status": "$PHP_FPM_STATUS",
      "last_restart": "$PHP_RESTART"
    },
    "redis": {
      "status": "$REDIS_STATUS",
      "last_restart": "$REDIS_RESTART"
    }
  },
  "application": {
    "active_connections": $ACTIVE_CONNECTIONS,
    "queue_size": "$QUEUE_SIZE",
    "cache_hits": "$CACHE_HITS",
    "cache_misses": "$CACHE_MISSES"
  }
}
EOF

    # Create human-readable summary
    cat > "$SUMMARY_FILE" << EOF
System Health Report - $(date)

SYSTEM METRICS:
- CPU Usage: ${CPU_USAGE}%
- Memory: ${MEM_USED}GB used / ${MEM_TOTAL}GB total (${MEM_USAGE_PERCENT}%)
- Disk /: ${DISK_ROOT}% used
- Disk /var: ${DISK_VAR}% used
- Load Average: $LOAD_1, $LOAD_5, $LOAD_15

SERVICES STATUS:
- Nginx: $NGINX_STATUS (Restarted: $NGINX_RESTART)
- MySQL: $MYSQL_STATUS (Restarted: $MYSQL_RESTART)
- PHP-FPM: $PHP_FPM_STATUS (Restarted: $PHP_RESTART)
- Redis: $REDIS_STATUS (Restarted: $REDIS_RESTART)

APPLICATION METRICS:
- Active Connections: $ACTIVE_CONNECTIONS
- Queue Size: $QUEUE_SIZE
- Cache Hits: $CACHE_HITS
- Cache Misses: $CACHE_MISSES

Report generated: $TIMESTAMP
EOF
}

# Send notifications
send_notifications() {
    # Check for critical issues
    CRITICAL_ISSUES=false

    if (( $(echo "$CPU_USAGE > 90" | bc -l) )); then
        CRITICAL_ISSUES=true
    fi

    if (( $(echo "$MEM_USAGE_PERCENT > 90" | bc -l) )); then
        CRITICAL_ISSUES=true
    fi

    if [ "$DISK_ROOT" -gt 90 ] || [ "$DISK_VAR" -gt 90 ]; then
        CRITICAL_ISSUES=true
    fi

    if [ "$NGINX_STATUS" != "active" ] || [ "$MYSQL_STATUS" != "active" ] || [ "$PHP_FPM_STATUS" != "active" ]; then
        CRITICAL_ISSUES=true
    fi

    # Send Slack notification
    if [ -n "$SLACK_WEBHOOK" ]; then
        if [ "$CRITICAL_ISSUES" = "true" ]; then
            curl -X POST "$SLACK_WEBHOOK" \
                 -H 'Content-type: application/json' \
                 -d "{\"text\":\"🚨 CRITICAL: System health issues detected\",\"attachments\":[{\"text\":\"$(cat "$SUMMARY_FILE")\"}]}" \
                 >/dev/null 2>&1 || true
        else
            curl -X POST "$SLACK_WEBHOOK" \
                 -H 'Content-type: application/json' \
                 -d "{\"text\":\"✅ Daily System Health Report\",\"attachments\":[{\"text\":\"$(cat "$SUMMARY_FILE")\"}]}" \
                 >/dev/null 2>&1 || true
        fi
    fi

    # Send email
    if [ -n "$EMAIL_RECIPIENT" ] && command -v mail >/dev/null 2>&1; then
        if [ "$CRITICAL_ISSUES" = "true" ]; then
            SUBJECT="CRITICAL: System Health Issues - $(hostname)"
        else
            SUBJECT="Daily System Health Report - $(hostname)"
        fi

        mail -s "$SUBJECT" "$EMAIL_RECIPIENT" < "$SUMMARY_FILE" || true
    fi
}

# Archive daily snapshot
archive_snapshot() {
    ARCHIVE_DIR="$REPORT_DIR/archive/$(date +%Y/%m)"
    mkdir -p "$ARCHIVE_DIR"
    cp "$REPORT_FILE" "$ARCHIVE_DIR/"
    cp "$SUMMARY_FILE" "$ARCHIVE_DIR/"

    # Clean old archives (keep 90 days)
    find "$REPORT_DIR/archive" -name "*.json" -o -name "*.txt" -mtime +90 -delete 2>/dev/null || true
}

# Main execution
log "Starting system health check"

collect_metrics
send_notifications
archive_snapshot

log "System health check completed. Report: $REPORT_FILE"

exit 0
