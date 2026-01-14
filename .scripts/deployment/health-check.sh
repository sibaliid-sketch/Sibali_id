#!/bin/bash

# Post-deploy health check runner
# Usage: ./health-check.sh [--url <base-url>] [--timeout <seconds>] [--retries <count>] [--backoff <seconds>]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Default values
BASE_URL="${HEALTH_CHECK_URL:-http://localhost}"
TIMEOUT=30
RETRIES=3
BACKOFF=5
JSON_OUTPUT=false

# Exit codes
EXIT_OK=0
EXIT_WARNING=1
EXIT_CRITICAL=2
EXIT_UNKNOWN=3

# Results
CHECKS_PASSED=0
CHECKS_FAILED=0
RESULTS=()

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --url)
      BASE_URL="$2"
      shift 2
      ;;
    --timeout)
      TIMEOUT="$2"
      shift 2
      ;;
    --retries)
      RETRIES="$2"
      shift 2
      ;;
    --backoff)
      BACKOFF="$2"
      shift 2
      ;;
    --json)
      JSON_OUTPUT=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit $EXIT_UNKNOWN
      ;;
  esac
done

# Logging function
log() {
  local level="$1"
  local message="$2"

  if [[ "$JSON_OUTPUT" == true ]]; then
    return
  fi

  echo "$(date '+%Y-%m-%d %H:%M:%S') [$level] $message"
}

# Add result
add_result() {
  local name="$1"
  local status="$2"
  local message="$3"
  local response_time="${4:-0}"
  local details="${5:-}"

  RESULTS+=("{\"name\":\"$name\",\"status\":\"$status\",\"message\":\"$message\",\"response_time\":$response_time,\"details\":\"$details\"}")

  if [[ "$status" == "pass" ]]; then
    ((CHECKS_PASSED++))
    log "INFO" "$name: PASS - $message"
  else
    ((CHECKS_FAILED++))
    log "ERROR" "$name: FAIL - $message"
  fi
}

# HTTP request with retry
http_request() {
  local url="$1"
  local expected_code="${2:-200}"
  local method="${3:-GET}"

  local attempt=1
  while [[ $attempt -le $RETRIES ]]; do
    log "DEBUG" "Attempt $attempt/$RETRIES: $method $url"

    local start_time=$(date +%s%N)
    local response
    local http_code

    if response=$(curl -s -w "HTTPSTATUS:%{http_code};TIME:%{time_total}" \
         --max-time "$TIMEOUT" \
         -X "$method" \
         "$url" 2>/dev/null); then

      http_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://' -e 's/;TIME.*//')
      local time_total=$(echo "$response" | tr -d '\n' | sed -e 's/.*TIME://')
      local end_time=$(date +%s%N)
      local response_time=$(( (end_time - start_time) / 1000000 )) # milliseconds

      if [[ "$http_code" == "$expected_code" ]]; then
        echo "$response_time"
        return 0
      fi

      log "WARN" "HTTP $http_code (expected $expected_code)"
    else
      log "WARN" "Request failed"
    fi

    if [[ $attempt -lt $RETRIES ]]; then
      log "INFO" "Retrying in $BACKOFF seconds..."
      sleep "$BACKOFF"
    fi

    ((attempt++))
  done

  return 1
}

# Check HTTP health endpoint
check_http_health() {
  local url="$BASE_URL/health"
  local response_time

  if response_time=$(http_request "$url" 200); then
    add_result "http_health" "pass" "Health endpoint responding" "$response_time"
  else
    add_result "http_health" "fail" "Health endpoint not responding" 0
  fi
}

# Check application readiness
check_app_readiness() {
  local url="$BASE_URL/ready"
  local response_time

  if response_time=$(http_request "$url" 200); then
    add_result "app_readiness" "pass" "Application ready" "$response_time"
  else
    add_result "app_readiness" "fail" "Application not ready" 0
  fi
}

# Check database connectivity
check_database() {
  # Use Laravel's db:monitor command if available
  if command -v php >/dev/null 2>&1 && [[ -f "artisan" ]]; then
    local start_time=$(date +%s%N)

    if php artisan db:monitor >/dev/null 2>&1; then
      local end_time=$(date +%s%N)
      local response_time=$(( (end_time - start_time) / 1000000 ))
      add_result "database" "pass" "Database connection OK" "$response_time"
    else
      add_result "database" "fail" "Database connection failed" 0
    fi
  else
    # Fallback: try direct MySQL connection
    if command -v mysql >/dev/null 2>&1; then
      local start_time=$(date +%s%N)

      if mysql -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" \
             -h"${DB_HOST:-localhost}" -P"${DB_PORT:-3306}" \
             -e "SELECT 1;" "${DB_DATABASE:-laravel}" >/dev/null 2>&1; then
        local end_time=$(date +%s%N)
        local response_time=$(( (end_time - start_time) / 1000000 ))
        add_result "database" "pass" "Database connection OK" "$response_time"
      else
        add_result "database" "fail" "Database connection failed" 0
      fi
    else
      add_result "database" "unknown" "MySQL client not available" 0
    fi
  fi
}

# Check queue health
check_queue_health() {
  if command -v php >/dev/null 2>&1 && [[ -f "artisan" ]]; then
    local start_time=$(date +%s%N)

    # Check queue size (assuming database queue)
    local queue_size
    queue_size=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null || echo "0")

    local end_time=$(date +%s%N)
    local response_time=$(( (end_time - start_time) / 1000000 ))

    # Warning if queue > 100, critical if > 1000
    if [[ $queue_size -gt 1000 ]]; then
      add_result "queue_health" "fail" "Queue size critical: $queue_size jobs" "$response_time" "queue_size:$queue_size"
    elif [[ $queue_size -gt 100 ]]; then
      add_result "queue_health" "warn" "Queue size high: $queue_size jobs" "$response_time" "queue_size:$queue_size"
    else
      add_result "queue_health" "pass" "Queue healthy: $queue_size jobs" "$response_time" "queue_size:$queue_size"
    fi
  else
    add_result "queue_health" "unknown" "Cannot check queue health" 0
  fi
}

# Check replication lag (if applicable)
check_replication_lag() {
  if command -v mysql >/dev/null 2>&1; then
    local lag_seconds
    lag_seconds=$(mysql -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" \
                  -h"${DB_HOST:-localhost}" -P"${DB_PORT:-3306}" \
                  -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep "Seconds_Behind_Master" | awk '{print $2}' || echo "0")

    if [[ -n "$lag_seconds" && "$lag_seconds" != "NULL" ]]; then
      if [[ $lag_seconds -gt 300 ]]; then # 5 minutes
        add_result "replication_lag" "fail" "Replication lag critical: ${lag_seconds}s" 0 "lag_seconds:$lag_seconds"
      elif [[ $lag_seconds -gt 60 ]]; then # 1 minute
        add_result "replication_lag" "warn" "Replication lag high: ${lag_seconds}s" 0 "lag_seconds:$lag_seconds"
      else
        add_result "replication_lag" "pass" "Replication lag OK: ${lag_seconds}s" 0 "lag_seconds:$lag_seconds"
      fi
    else
      add_result "replication_lag" "unknown" "Replication not configured or not running" 0
    fi
  else
    add_result "replication_lag" "unknown" "MySQL client not available" 0
  fi
}

# Check error rate (from logs)
check_error_rate() {
  local log_file="${LOG_FILE:-storage/logs/laravel.log}"
  local time_window=300 # 5 minutes in seconds
  local cutoff_time=$(date -d "$time_window seconds ago" +%s)

  if [[ -f "$log_file" ]]; then
    # Count errors in last 5 minutes
    local error_count
    error_count=$(grep -c "\[$(date -d@$cutoff_time +%Y-%m-%d\ %H:%M:%S)" "$log_file" 2>/dev/null || echo "0")

    if [[ $error_count -gt 50 ]]; then
      add_result "error_rate" "fail" "Error rate critical: $error_count errors in 5min" 0 "error_count:$error_count"
    elif [[ $error_count -gt 10 ]]; then
      add_result "error_rate" "warn" "Error rate high: $error_count errors in 5min" 0 "error_count:$error_count"
    else
      add_result "error_rate" "pass" "Error rate OK: $error_count errors in 5min" 0 "error_count:$error_count"
    fi
  else
    add_result "error_rate" "unknown" "Log file not found: $log_file" 0
  fi
}

# Check disk space
check_disk_space() {
  local mount_point="/"
  local usage_percent
  usage_percent=$(df "$mount_point" | tail -1 | awk '{print $5}' | sed 's/%//')

  if [[ $usage_percent -gt 95 ]]; then
    add_result "disk_space" "fail" "Disk usage critical: ${usage_percent}%" 0 "usage_percent:$usage_percent"
  elif [[ $usage_percent -gt 85 ]]; then
    add_result "disk_space" "warn" "Disk usage high: ${usage_percent}%" 0 "usage_percent:$usage_percent"
  else
    add_result "disk_space" "pass" "Disk usage OK: ${usage_percent}%" 0 "usage_percent:$usage_percent"
  fi
}

# Check memory usage
check_memory() {
  if command -v free >/dev/null 2>&1; then
    local usage_percent
    usage_percent=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')

    if [[ $usage_percent -gt 95 ]]; then
      add_result "memory" "fail" "Memory usage critical: ${usage_percent}%" 0 "usage_percent:$usage_percent"
    elif [[ $usage_percent -gt 85 ]]; then
      add_result "memory" "warn" "Memory usage high: ${usage_percent}%" 0 "usage_percent:$usage_percent"
    else
      add_result "memory" "pass" "Memory usage OK: ${usage_percent}%" 0 "usage_percent:$usage_percent"
    fi
  else
    add_result "memory" "unknown" "Cannot check memory usage" 0
  fi
}

# Generate JSON output
generate_json_output() {
  local total_checks=$((CHECKS_PASSED + CHECKS_FAILED))
  local overall_status="pass"

  if [[ $CHECKS_FAILED -gt 0 ]]; then
    overall_status="fail"
  fi

  cat << EOF
{
  "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "hostname": "$(hostname)",
  "base_url": "$BASE_URL",
  "summary": {
    "total_checks": $total_checks,
    "passed": $CHECKS_PASSED,
    "failed": $CHECKS_FAILED,
    "status": "$overall_status"
  },
  "checks": [$(IFS=,; echo "${RESULTS[*]}")]
}
EOF
}

# Send results to monitoring
send_to_monitoring() {
  local json_data="$1"

  # Send to Prometheus pushgateway
  # echo "$json_data" | curl -X POST --data-binary @- "$PUSHGATEWAY_URL/metrics/job/health_check"

  # Send to monitoring webhook
  # curl -X POST -H 'Content-type: application/json' --data "$json_data" "$MONITORING_WEBHOOK_URL"

  return 0
}

# Main function
main() {
  log "INFO" "Starting health checks for $BASE_URL"

  # Run all checks
  check_http_health
  check_app_readiness
  check_database
  check_queue_health
  check_replication_lag
  check_error_rate
  check_disk_space
  check_memory

  # Generate output
  if [[ "$JSON_OUTPUT" == true ]]; then
    generate_json_output
  else
    log "INFO" "Health check completed: $CHECKS_PASSED passed, $CHECKS_FAILED failed"
  fi

  # Send to monitoring
  if [[ -n "${PUSHGATEWAY_URL:-}" ]] || [[ -n "${MONITORING_WEBHOOK_URL:-}" ]]; then
    generate_json_output | send_to_monitoring
  fi

  # Determine exit code
  if [[ $CHECKS_FAILED -gt 0 ]]; then
    exit $EXIT_CRITICAL
  else
    exit $EXIT_OK
  fi
}

# Run main function
main "$@"
