#!/bin/bash

# Booking Data Backup Script for Sibali.id
# Purpose: Helper export data reservasi/booking untuk reporting atau archival
# Function: Export booking data with configurable options

set -e

# Configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-backup_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-u486134328_sibaliid}"
BACKUP_DIR="/var/backups/sibali/bookings"
OUTPUT_FORMAT="${OUTPUT_FORMAT:-csv}"
DATE_FROM="${DATE_FROM:-$(date -d '30 days ago' +%Y-%m-%d)}"
DATE_TO="${DATE_TO:-$(date +%Y-%m-%d)}"
ENCRYPT="${ENCRYPT:-false}"
GPG_RECIPIENT="${GPG_RECIPIENT:-}"
LOG_FILE="/var/log/sibali/booking_backup.log"
RETENTION_DAYS="${RETENTION_DAYS:-90}"

# Ensure directories exist
mkdir -p "$BACKUP_DIR" "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Generate filename
generate_filename() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local format="$OUTPUT_FORMAT"
    local encrypted_suffix=""

    if [ "$ENCRYPT" = "true" ]; then
        encrypted_suffix=".gpg"
    fi

    echo "bookings_${DATE_FROM}_to_${DATE_TO}_${timestamp}.${format}${encrypted_suffix}"
}

# Export to CSV
export_csv() {
    local output_file="$1"

    log "Exporting bookings to CSV..."

    # CSV Header
    cat > "$output_file" << EOF
id,student_id,class_id,booking_date,status,created_at,updated_at,student_name,class_name,teacher_name
EOF

    # Export data
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT
            b.id,
            b.student_id,
            b.class_id,
            b.created_at as booking_date,
            b.status,
            b.created_at,
            b.updated_at,
            COALESCE(s.name, '') as student_name,
            COALESCE(c.name, '') as class_name,
            COALESCE(t.name, '') as teacher_name
        FROM class_bookings b
        LEFT JOIN students s ON b.student_id = s.id
        LEFT JOIN classes c ON b.class_id = c.id
        LEFT JOIN employees t ON c.teacher_id = t.id
        WHERE DATE(b.created_at) BETWEEN '$DATE_FROM' AND '$DATE_TO'
        ORDER BY b.created_at DESC
    " 2>/dev/null | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g' >> "$output_file"

    log "CSV export completed"
}

# Export to JSON
export_json() {
    local output_file="$1"

    log "Exporting bookings to JSON..."

    # Export data as JSON
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT
            JSON_OBJECT(
                'id', b.id,
                'student_id', b.student_id,
                'class_id', b.class_id,
                'booking_date', b.created_at,
                'status', b.status,
                'created_at', b.created_at,
                'updated_at', b.updated_at,
                'student', JSON_OBJECT(
                    'id', s.id,
                    'name', s.name,
                    'email', s.email
                ),
                'class', JSON_OBJECT(
                    'id', c.id,
                    'name', c.name,
                    'schedule', c.schedule
                ),
                'teacher', JSON_OBJECT(
                    'id', t.id,
                    'name', t.name
                )
            ) as booking_json
        FROM class_bookings b
        LEFT JOIN students s ON b.student_id = s.id
        LEFT JOIN classes c ON b.class_id = c.id
        LEFT JOIN employees t ON c.teacher_id = t.id
        WHERE DATE(b.created_at) BETWEEN '$DATE_FROM' AND '$DATE_TO'
        ORDER BY b.created_at DESC
    " 2>/dev/null | jq -s '.' > "$output_file"

    log "JSON export completed"
}

# Encrypt file
encrypt_file() {
    local input_file="$1"
    local output_file="$2"

    if [ "$ENCRYPT" = "true" ]; then
        log "Encrypting backup file..."

        if [ -n "$GPG_RECIPIENT" ]; then
            gpg --encrypt --recipient "$GPG_RECIPIENT" --output "$output_file" "$input_file"
        else
            # Symmetric encryption
            gpg --symmetric --output "$output_file" "$input_file"
        fi

        # Remove unencrypted file
        rm -f "$input_file"

        log "File encrypted"
    else
        mv "$input_file" "$output_file"
    fi
}

# Generate checksum
generate_checksum() {
    local file="$1"
    local checksum_file="$file.sha256"

    sha256sum "$file" > "$checksum_file"
    log "Checksum generated: $checksum_file"
}

# Create metadata
create_metadata() {
    local data_file="$1"
    local meta_file="$data_file.meta.json"

    local record_count=0
    if [ "$OUTPUT_FORMAT" = "csv" ]; then
        record_count=$(wc -l < "$data_file")
        record_count=$((record_count - 1))  # Subtract header
    elif [ "$OUTPUT_FORMAT" = "json" ]; then
        record_count=$(jq '. | length' "$data_file" 2>/dev/null || echo "0")
    fi

    local file_size=$(stat -f%z "$data_file" 2>/dev/null || stat -c%s "$data_file")

    cat > "$meta_file" << EOF
{
  "backup_type": "bookings",
  "format": "$OUTPUT_FORMAT",
  "date_from": "$DATE_FROM",
  "date_to": "$DATE_FROM",
  "record_count": $record_count,
  "file_size_bytes": $file_size,
  "encrypted": $ENCRYPT,
  "created_at": "$(date -Iseconds)",
  "created_by": "$(whoami)",
  "retention_days": $RETENTION_DAYS,
  "checksum": "$(sha256sum "$data_file" | cut -d' ' -f1)"
}
EOF

    log "Metadata created: $meta_file"
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up old backups (retention: ${RETENTION_DAYS} days)..."

    find "$BACKUP_DIR" -name "bookings_*.${OUTPUT_FORMAT}*" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    find "$BACKUP_DIR" -name "bookings_*.meta.json" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    find "$BACKUP_DIR" -name "bookings_*.sha256" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true

    log "Cleanup completed"
}

# Validate date range
validate_dates() {
    if ! date -d "$DATE_FROM" >/dev/null 2>&1; then
        log "ERROR: Invalid DATE_FROM: $DATE_FROM"
        exit 1
    fi

    if ! date -d "$DATE_TO" >/dev/null 2>&1; then
        log "ERROR: Invalid DATE_TO: $DATE_TO"
        exit 1
    fi

    if [[ "$DATE_FROM" > "$DATE_TO" ]]; then
        log "ERROR: DATE_FROM cannot be after DATE_TO"
        exit 1
    fi
}

# Main execution
log "Starting booking data backup"
log "Date range: $DATE_FROM to $DATE_TO"
log "Format: $OUTPUT_FORMAT, Encrypted: $ENCRYPT"

validate_dates

# Generate temporary file
temp_file=$(mktemp)
final_filename=$(generate_filename)
final_file="$BACKUP_DIR/$final_filename"

# Export data
case "$OUTPUT_FORMAT" in
    csv)
        export_csv "$temp_file"
        ;;
    json)
        export_json "$temp_file"
        ;;
    *)
        log "ERROR: Unsupported format: $OUTPUT_FORMAT"
        exit 1
        ;;
esac

# Encrypt if requested
encrypt_file "$temp_file" "$final_file"

# Generate checksum and metadata
generate_checksum "$final_file"
create_metadata "$final_file"

# Cleanup old backups
cleanup_old_backups

log "Booking backup completed: $final_file"
log "Records exported: $(grep '"record_count"' "$final_file.meta.json" | cut -d: -f2 | tr -d ' ,')"

exit 0
