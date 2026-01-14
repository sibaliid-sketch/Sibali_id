#!/bin/bash

# Performance Check Script for Sibali.id
# Purpose: Snapshots metrik performa periodik untuk tracking regresi
# Function: Capture performance metrics and compare with baselines

set -e

# Configuration
LOG_DIR="/var/log/sibali"
REPORT_DIR="/var/reports/performance"
BASELINE_FILE="$REPORT_DIR/baseline.json"
CURRENT_REPORT="$REPORT_DIR/perf_report_$(date +%Y%m%d_%H%M%S).json"
ALERT_WEBHOOK="${ALERT_WEBHOOK:-}"
FLAMEGRAPH_DIR="$REPORT_DIR/flamegraphs"

# Ensure directories exist
mkdir -p "$LOG_DIR" "$REPORT_DIR" "$FLAMEGRAPH_DIR"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOG_DIR/performance_monitor.log"
}

# Capture application performance metrics
capture_app_metrics() {
    log "Capturing application performance metrics..."

    # Laravel response times (if using Laravel Telescope or custom logging)
    RESPONSE_P50=$(grep "response_time" /var/log/sibali/laravel.log 2>/dev/null | tail -100 | awk '{print $NF}' | sort -n | awk 'NR==50{print $1}' || echo "0")
    RESPONSE_P95=$(grep "response_time" /var/log/sibali/laravel.log 2>/dev/null | tail -100 | awk '{print $NF}' | sort -n | awk 'NR==95{print $1}' || echo "0")
    RESPONSE_P99=$(grep "response_time" /var/log/sibali/laravel.log 2>/dev/null | tail -100 | awk '{print $NF}' | sort -n | awk 'NR==99{print $1}' || echo "0")

    # Throughput (requests per minute)
    REQUESTS_PER_MIN=$(grep "$(date '+%Y-%m-%d %H:%M')" /var/log/sibali/access.log 2>/dev/null | wc -l || echo "0")

    # Error rate
    ERROR_RATE=$(grep "$(date '+%Y-%m-%d %H:%M')" /var/log/sibali/laravel.log 2>/dev/null | grep -c "ERROR\|CRITICAL" || echo "0")

    # Queue metrics
    QUEUE_SIZE=$(php artisan queue:status 2>/dev/null | grep -oP '(?<=size: )\d+' | paste -sd+ | bc || echo "0")
    QUEUE_LATENCY=$(php artisan queue:status 2>/dev/null | grep -oP '(?<=latency: )\d+' | paste -sd+ | bc || echo "0")

    # Cache performance
    CACHE_HITS=$(redis-cli info stats 2>/dev/null | grep keyspace_hits | cut -d: -f2 | xargs || echo "0")
    CACHE_MISSES=$(redis-cli info stats 2>/dev/null | grep keyspace_misses | cut -d: -f2 | xargs || echo "0")
    CACHE_RATIO="0"
    if [ "$CACHE_HITS" != "0" ] || [ "$CACHE_MISSES" != "0" ]; then
        CACHE_RATIO=$(echo "scale=2; $CACHE_HITS / ($CACHE_HITS + $CACHE_MISSES) * 100" | bc -l 2>/dev/null || echo "0")
    fi

    # Memory usage
    PHP_MEMORY=$(ps aux | grep "php-fpm" | grep -v grep | awk '{sum += $6} END {print sum/1024}' || echo "0")

    cat << EOF
{
  "timestamp": "$(date +%s)",
  "datetime": "$(date)",
  "response_times": {
    "p50_ms": $RESPONSE_P50,
    "p95_ms": $RESPONSE_P95,
    "p99_ms": $RESPONSE_P99
  },
  "throughput": {
    "requests_per_minute": $REQUESTS_PER_MIN
  },
  "errors": {
    "error_rate_per_minute": $ERROR_RATE
  },
  "queue": {
    "total_size": $QUEUE_SIZE,
    "average_latency_ms": $QUEUE_LATENCY
  },
  "cache": {
    "hit_ratio_percent": $CACHE_RATIO,
    "hits": $CACHE_HITS,
    "misses": $CACHE_MISSES
  },
  "memory": {
    "php_memory_mb": $PHP_MEMORY
  }
}
EOF
}

# Capture database performance metrics
capture_db_metrics() {
    log "Capturing database performance metrics..."

    # MySQL slow queries
    SLOW_QUERIES=$(mysql -e "SHOW PROCESSLIST" 2>/dev/null | grep -c "Query" || echo "0")

    # Connection count
    DB_CONNECTIONS=$(mysql -e "SHOW PROCESSLIST" 2>/dev/null | wc -l || echo "0")

    # InnoDB metrics
    INNODB_BUFFER_POOL_HIT_RATIO=$(mysql -e "SHOW ENGINE INNODB STATUS\G" 2>/dev/null | grep "Buffer pool hit rate" | awk '{print $5}' | sed 's/%//' || echo "0")

    # Query execution time percentiles (from slow log if available)
    QUERY_P95_TIME=$(mysql -e "SELECT AVG(query_time) FROM performance_schema.events_statements_summary_by_digest WHERE query_time > 0 ORDER BY query_time DESC LIMIT 5" 2>/dev/null | tail -1 || echo "0")

    cat << EOF
{
  "slow_queries_count": $SLOW_QUERIES,
  "active_connections": $DB_CONNECTIONS,
  "innodb_buffer_pool_hit_ratio": $INNODB_BUFFER_POOL_HIT_RATIO,
  "query_p95_time_ms": $QUERY_P95_TIME
}
EOF
}

# Generate flamegraph (if perf tools available)
generate_flamegraph() {
    if command -v perf >/dev/null 2>&1 && command -v php >/dev/null 2>&1; then
        log "Generating flamegraph..."

        FLAMEGRAPH_FILE="$FLAMEGRAPH_DIR/flamegraph_$(date +%Y%m%d_%H%M%S).svg"

        # Record PHP performance for 30 seconds
        timeout 30 perf record -F 99 -p $(pgrep -f "php-fpm" | head -1) -g -- sleep 10 2>/dev/null || true

        # Generate flamegraph
        perf script | stackcollapse-perf.pl | flamegraph.pl > "$FLAMEGRAPH_FILE" 2>/dev/null || true

        if [ -f "$FLAMEGRAPH_FILE" ]; then
            echo "\"$FLAMEGRAPH_FILE\""
        else
            echo "null"
        fi
    else
        echo "null"
    fi
}

# Compare with baseline
compare_baseline() {
    if [ ! -f "$BASELINE_FILE" ]; then
        log "No baseline file found, creating initial baseline"
        cp "$CURRENT_REPORT" "$BASELINE_FILE"
        return
    fi

    # Simple threshold-based comparison (can be enhanced with statistical analysis)
    BASELINE_P95=$(jq '.response_times.p95_ms' "$BASELINE_FILE" 2>/dev/null || echo "0")
    CURRENT_P95=$(jq '.response_times.p95_ms' "$CURRENT_REPORT" 2>/dev/null || echo "0")

    BASELINE_ERROR_RATE=$(jq '.errors.error_rate_per_minute' "$BASELINE_FILE" 2>/dev/null || echo "0")
    CURRENT_ERROR_RATE=$(jq '.errors.error_rate_per_minute' "$CURRENT_REPORT" 2>/dev/null || echo "0")

    ALERTS=""

    # Check response time regression (50% increase)
    if (( $(echo "$CURRENT_P95 > $BASELINE_P95 * 1.5" | bc -l 2>/dev/null || echo "0") )); then
        ALERTS="$ALERTS Response time regression detected (P95: ${CURRENT_P95}ms > baseline ${BASELINE_P95}ms)\n"
    fi

    # Check error rate increase
    if (( $(echo "$CURRENT_ERROR_RATE > $BASELINE_ERROR_RATE * 2" | bc -l 2>/dev/null || echo "0") )); then
        ALERTS="$ALERTS Error rate increase detected (Current: $CURRENT_ERROR_RATE/min > baseline $BASELINE_ERROR_RATE/min)\n"
    fi

    if [ -n "$ALERTS" ]; then
        log "PERFORMANCE ALERTS DETECTED:"
        echo -e "$ALERTS" | log

        # Send alert
        if [ -n "$ALERT_WEBHOOK" ]; then
            curl -X POST "$ALERT_WEBHOOK" \
                 -H 'Content-type: application/json' \
                 -d "{\"text\":\"🚨 Performance Regression Alert\",\"attachments\":[{\"text\":\"$ALERTS\"}]}" \
                 >/dev/null 2>&1 || true
        fi
    fi
}

# Main execution
log "Starting performance check"

# Capture all metrics
APP_METRICS=$(capture_app_metrics)
DB_METRICS=$(capture_db_metrics)
FLAMEGRAPH=$(generate_flamegraph)

# Combine into final report
cat > "$CURRENT_REPORT" << EOF
{
  "timestamp": "$(date +%s)",
  "datetime": "$(date)",
  "application": $APP_METRICS,
  "database": $DB_METRICS,
  "flamegraph": $FLAMEGRAPH
}
EOF

# Compare with baseline
compare_baseline

log "Performance check completed. Report: $CURRENT_REPORT"

# Archive old reports (keep 30 days)
find "$REPORT_DIR" -name "perf_report_*.json" -mtime +30 -delete 2>/dev/null || true

exit 0
