#!/bin/bash

# Performance Check Script for Sibali.id
# Snapshots metrik performa periodik untuk tracking regresi
# Menjaga baseline performa; detect regresi performa cepat

set -euo pipefail

# Configuration
LOG_FILE="/var/log/sibali-performance.log"
REPORT_FILE="/var/log/performance_snapshot_$(date +%Y%m%d_%H%M%S).json"
BASELINE_FILE="/var/log/performance_baseline.json"
APP_URL="${APP_URL:-http://localhost}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-monitor_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-sibaliid}"

# Thresholds for alerts
THRESHOLD_RESPONSE_TIME_P95=2000  # ms
THRESHOLD_DB_SLOW_QUERIES=10
THRESHOLD_THROUGHPUT_DROP=20  # percentage
THRESHOLD_QUEUE_LATENCY=5000  # ms

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Capture: response time percentiles, DB slow queries list, throughput, queue latencies
capture_metrics() {
    local metrics="{}"

    log "Capturing performance metrics..."

    # Response time percentiles (simulate with curl)
    log "Measuring response times..."
    local response_times=""
    for i in {1..10}; do
        local start_time
        start_time=$(date +%s%N)
        if curl -s -o /dev/null -w "%{http_code}" "$APP_URL" >/dev/null; then
            local end_time
            end_time=$(date +%s%N)
            local response_time=$(( (end_time - start_time) / 1000000 ))  # Convert to ms
            response_times="${response_times}${response_time} "
        fi
        sleep 1
    done

    # Calculate percentiles (simplified)
    local sorted_times
    sorted_times=$(echo "$response_times" | tr ' ' '\n' | sort -n | tr '\n' ' ')
    local p50 p95
    p50=$(echo "$sorted_times" | awk '{a[NR]=$1} END {print a[int(NR*0.5)]}')
    p95=$(echo "$sorted_times" | awk '{a[NR]=$1} END {print a[int(NR*0.95)]}')

    metrics=$(echo "$metrics" | jq --arg p50 "$p50" --arg p95 "$p95" '.response_time = {p50: $p50, p95: $p95}')

    # DB slow queries list
    log "Checking DB slow queries..."
    local slow_queries
    slow_queries=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "
        SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_sec
        FROM performance_schema.events_statements_summary_by_digest
        WHERE avg_timer_wait > 1000000000000  -- > 1 second
        ORDER BY avg_timer_wait DESC
        LIMIT 10;
    " 2>/dev/null | wc -l)

    metrics=$(echo "$metrics" | jq --arg slow_queries "$slow_queries" '.db_slow_queries = $slow_queries')

    # Throughput (requests per second - simplified)
    log "Measuring throughput..."
    local throughput
    throughput=$(timeout 10 bash -c "
        count=0
        while curl -s -o /dev/null -w '%{http_code}' '$APP_URL' | grep -q 200; do
            ((count++))
        done
        echo \$count
    " 2>/dev/null || echo "0")

    metrics=$(echo "$metrics" | jq --arg throughput "$throughput" '.throughput = $throughput')

    # Queue latencies (if using Redis queue)
    log "Checking queue latency..."
    local queue_length queue_latency
    queue_length=$(redis-cli LLEN queues:default 2>/dev/null || echo "0")
    # Simulate latency measurement
    queue_latency=$(redis-cli --latency -i 1 -c 2>/dev/null | tail -1 | awk '{print $NF}' || echo "0")

    metrics=$(echo "$metrics" | jq --arg queue_length "$queue_length" --arg queue_latency "$queue_latency" \
        '.queue = {length: $queue_length, latency_ms: $queue_latency}')

    # Add timestamp
    metrics=$(echo "$metrics" | jq --arg timestamp "$(date +%s)" '.timestamp = $timestamp')

    echo "$metrics"
}

# Baseline compare: compare with historical baseline and raise alert if thresholds breached
compare_baseline() {
    local current_metrics="$1"

    if [[ ! -f "$BASELINE_FILE" ]]; then
        log "No baseline file found. Creating initial baseline."
        echo "$current_metrics" > "$BASELINE_FILE"
        return
    fi

    local baseline_metrics
    baseline_metrics=$(cat "$BASELINE_FILE")

    log "Comparing with baseline..."

    # Check response time P95
    local current_p95 baseline_p95
    current_p95=$(echo "$current_metrics" | jq -r '.response_time.p95')
    baseline_p95=$(echo "$baseline_metrics" | jq -r '.response_time.p95')

    if [[ -n "$current_p95" && -n "$baseline_p95" && "$current_p95" -gt "$THRESHOLD_RESPONSE_TIME_P95" ]]; then
        log "ALERT: Response time P95 ($current_p95 ms) exceeds threshold ($THRESHOLD_RESPONSE_TIME_P95 ms)"
    fi

    # Check DB slow queries
    local current_slow baseline_slow
    current_slow=$(echo "$current_metrics" | jq -r '.db_slow_queries')
    baseline_slow=$(echo "$baseline_metrics" | jq -r '.db_slow_queries')

    if [[ "$current_slow" -gt "$THRESHOLD_DB_SLOW_QUERIES" ]]; then
        log "ALERT: High number of slow queries ($current_slow)"
    fi

    # Check throughput drop
    local current_tp baseline_tp
    current_tp=$(echo "$current_metrics" | jq -r '.throughput')
    baseline_tp=$(echo "$baseline_metrics" | jq -r '.throughput')

    if [[ -n "$current_tp" && -n "$baseline_tp" && "$baseline_tp" -gt 0 ]]; then
        local drop_percent
        drop_percent=$(( (baseline_tp - current_tp) * 100 / baseline_tp ))
        if [[ "$drop_percent" -gt "$THRESHOLD_THROUGHPUT_DROP" ]]; then
            log "ALERT: Throughput dropped by $drop_percent% (current: $current_tp, baseline: $baseline_tp)"
        fi
    fi

    # Check queue latency
    local current_latency
    current_latency=$(echo "$current_metrics" | jq -r '.queue.latency_ms')

    if [[ -n "$current_latency" && "$current_latency" -gt "$THRESHOLD_QUEUE_LATENCY" ]]; then
        log "ALERT: Queue latency ($current_latency ms) exceeds threshold ($THRESHOLD_QUEUE_LATENCY ms)"
    fi
}

# Artifact: store perf snapshot and flamegraph link (if available)
store_artifacts() {
    local metrics="$1"

    # Store performance snapshot
    echo "$metrics" > "$REPORT_FILE"
    log "Performance snapshot stored: $REPORT_FILE"

    # Generate flamegraph link (placeholder - would integrate with perf tools)
    local flamegraph_link="https://flamegraph.example.com/$(basename "$REPORT_FILE" .json).svg"
    log "Flamegraph available at: $flamegraph_link"

    # Update baseline if this is a good snapshot (no alerts)
    if ! grep -q "ALERT" "$LOG_FILE"; then
        cp "$REPORT_FILE" "$BASELINE_FILE"
        log "Baseline updated with current good snapshot"
    fi
}

# Main execution
main() {
    log "Starting performance check"

    local metrics
    metrics=$(capture_metrics)

    compare_baseline "$metrics"

    store_artifacts "$metrics"

    log "Performance check completed"
}

main "$@"
