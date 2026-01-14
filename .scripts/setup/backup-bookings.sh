#!/bin/bash

# Booking Data Backup Script for Sibali.id
# Exports reservation/booking data for reporting and archival

set -e

# Configuration
OUTPUT_DIR="./storage/backups/bookings"
LOG_FILE="./storage/logs/booking_backup_$(date +"%Y%m%d_%H%M%S").log"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-laravel}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

# Export configuration
EXPORT_FORMAT="${EXPORT_FORMAT:-csv}"  # csv or json
DATE_FROM="${DATE_FROM:-$(date -d '1 month ago' +%Y-%m-%d)}"
DATE_TO="${DATE_TO:-$(date +%Y-%m-%d)}"
ENCRYPT_BACKUP="${ENCRYPT_BACKUP:-true}"
RETENTION_DAYS="${RETENTION_DAYS:-365}"
INCREMENTAL="${INCREMENTAL:-false}"

# Columns to export (configurable)
BOOKING_COLUMNS="id,user_id,product_kode,tingkat_pendidikan,layanan,program,kelas,jumlah_pertemuan,harga_kelas,satuan,created_at,updated_at,status"
USER_COLUMNS="id,name,email,phone,created_at"

# Create directories
mkdir -p "$OUTPUT_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Show usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Export booking data for Sibali.id"
    echo ""
    echo "OPTIONS:"
    echo "  -f, --format FORMAT    Export format: csv or json (default: csv)"
    echo "  -s, --start DATE       Start date (YYYY-MM-DD, default: 1 month ago)"
    echo "  -e, --end DATE         End date (YYYY-MM-DD, default: today)"
    echo "  -i, --incremental      Perform incremental export"
    echo "  -n, --no-encrypt       Skip encryption"
    echo "  -h, --help             Show this help"
    echo ""
    echo "Examples:"
    echo "  $0 --format json --start 2024-01-01 --end 2024-01-31"
    echo "  $0 --incremental  # Export only new/changed records"
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -f|--format)
            EXPORT_FORMAT="$2"
            shift 2
            ;;
        -s|--start)
            DATE_FROM="$2"
            shift 2
            ;;
        -e|--end)
            DATE_TO="$2"
            shift 2
            ;;
        -i|--incremental)
            INCREMENTAL=true
            shift
            ;;
        -n|--no-encrypt)
            ENCRYPT_BACKUP=false
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            usage
            exit 1
            ;;
    esac
done

# Validate dates
validate_date() {
    if ! date -d "$1" >/dev/null 2>&1; then
        log "ERROR: Invalid date format: $1"
        exit 1
    fi
}

validate_date "$DATE_FROM"
validate_date "$DATE_TO"

# Generate filename
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="bookings_${DATE_FROM}_${DATE_TO}_${TIMESTAMP}"
OUTPUT_FILE="$OUTPUT_DIR/${FILENAME}.$EXPORT_FORMAT"

# Function to test database connection
test_db_connection() {
    if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" "$DB_NAME" >/dev/null 2>&1; then
        log "ERROR: Cannot connect to database"
        exit 1
    fi
}

# Function to get last export timestamp for incremental
get_last_export_timestamp() {
    local last_file
    last_file=$(find "$OUTPUT_DIR" -name "bookings_*.${EXPORT_FORMAT}" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2-)

    if [ -n "$last_file" ] && [ -f "$last_file" ]; then
        # Extract timestamp from filename or file modification time
        stat -c %Y "$last_file" 2>/dev/null || stat -f %m "$last_file" 2>/dev/null || date +%s
    else
        echo "0"  # No previous export
    fi
}

# Function to export bookings to CSV
export_to_csv() {
    log "Exporting bookings to CSV format..."

    # Create temporary SQL file
    local sql_file="/tmp/booking_export_$$.sql"

    cat > "$sql_file" << EOF
SELECT
    b.id,
    b.user_id,
    b.product_kode,
    b.tingkat_pendidikan,
    b.layanan,
    b.program,
    b.kelas,
    b.jumlah_pertemuan,
    b.harga_kelas,
    b.satuan,
    b.created_at,
    b.updated_at,
    b.status,
    u.name as user_name,
    u.email as user_email,
    u.phone as user_phone
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
WHERE b.created_at BETWEEN '$DATE_FROM 00:00:00' AND '$DATE_TO 23:59:59'
EOF

    if [ "$INCREMENTAL" = true ]; then
        local last_timestamp
        last_timestamp=$(get_last_export_timestamp)
        echo "AND b.updated_at > FROM_UNIXTIME($last_timestamp)" >> "$sql_file"
        log "Incremental export: only records updated after $(date -d "@$last_timestamp" 2>/dev/null || echo "timestamp $last_timestamp")"
    fi

    echo "ORDER BY b.created_at DESC;" >> "$sql_file"

    # Execute query and export to CSV
    {
        echo "# Booking Data Export"
        echo "# Generated: $(date)"
        echo "# Date Range: $DATE_FROM to $DATE_TO"
        echo "# Format: CSV"
        echo "# Columns: id,user_id,product_kode,tingkat_pendidikan,layanan,program,kelas,jumlah_pertemuan,harga_kelas,satuan,created_at,updated_at,status,user_name,user_email,user_phone"
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
    } > "$OUTPUT_FILE"

    # Clean up
    rm -f "$sql_file"

    log "CSV export completed: $OUTPUT_FILE"
}

# Function to export bookings to JSON
export_to_json() {
    log "Exporting bookings to JSON format..."

    # Create temporary SQL file
    local sql_file="/tmp/booking_export_$$.sql"

    cat > "$sql_file" << EOF
SELECT
    JSON_OBJECT(
        'id', b.id,
        'user_id', b.user_id,
        'product_kode', b.product_kode,
        'tingkat_pendidikan', b.tingkat_pendidikan,
        'layanan', b.layanan,
        'program', b.program,
        'kelas', b.kelas,
        'jumlah_pertemuan', b.jumlah_pertemuan,
        'harga_kelas', b.harga_kelas,
        'satuan', b.satuan,
        'created_at', b.created_at,
        'updated_at', b.updated_at,
        'status', b.status,
        'user', JSON_OBJECT(
            'name', u.name,
            'email', u.email,
            'phone', u.phone
        )
    ) as booking_json
FROM bookings b
LEFT JOIN users u ON b.user_id = u.id
WHERE b.created_at BETWEEN '$DATE_FROM 00:00:00' AND '$DATE_TO 23:59:59'
EOF

    if [ "$INCREMENTAL" = true ]; then
        local last_timestamp
        last_timestamp=$(get_last_export_timestamp)
        echo "AND b.updated_at > FROM_UNIXTIME($last_timestamp)" >> "$sql_file"
        log "Incremental export: only records updated after $(date -d "@$last_timestamp" 2>/dev/null || echo "timestamp $last_timestamp")"
    fi

    echo "ORDER BY b.created_at DESC;" >> "$sql_file"

    # Execute query and format as JSON array
    {
        echo "{"
        echo "  \"export_info\": {"
        echo "    \"generated_at\": \"$(date -Iseconds)\","
        echo "    \"date_range\": {\"from\": \"$DATE_FROM\", \"to\": \"$DATE_TO\"},"
        echo "    \"format\": \"JSON\","
        echo "    \"incremental\": $INCREMENTAL"
        echo "  },"
        echo "  \"bookings\": ["
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file" | sed '$!s/$/,/' | sed 's/^/    /'
        echo "  ]"
        echo "}"
    } > "$OUTPUT_FILE"

    # Clean up
    rm -f "$sql_file"

    log "JSON export completed: $OUTPUT_FILE"
}

# Function to encrypt backup
encrypt_backup() {
    if [ "$ENCRYPT_BACKUP" != true ]; then
        log "Skipping encryption as requested"
        return
    fi

    log "Encrypting backup file..."

    # Check if gpg is available
    if ! command -v gpg &> /dev/null; then
        log "WARNING: GPG not available, skipping encryption"
        return
    fi

    # Encrypt file
    local encrypted_file="${OUTPUT_FILE}.gpg"
    if gpg --batch --yes --passphrase-file <(echo "$BACKUP_PASSPHRASE") -c "$OUTPUT_FILE"; then
        mv "${OUTPUT_FILE}.gpg" "$encrypted_file"
        rm "$OUTPUT_FILE"
        OUTPUT_FILE="$encrypted_file"
        log "Backup encrypted: $OUTPUT_FILE"
    else
        log "WARNING: Encryption failed, keeping unencrypted file"
    fi
}

# Function to generate checksum
generate_checksum() {
    local checksum_file="${OUTPUT_FILE}.sha256"
    sha256sum "$OUTPUT_FILE" > "$checksum_file"
    log "Checksum generated: $checksum_file"
}

# Function to add retention metadata
add_retention_metadata() {
    local metadata_file="${OUTPUT_FILE}.meta"

    cat > "$metadata_file" << EOF
{
  "filename": "$(basename "$OUTPUT_FILE")",
  "created_at": "$(date -Iseconds)",
  "date_range": {
    "from": "$DATE_FROM",
    "to": "$DATE_TO"
  },
  "format": "$EXPORT_FORMAT",
  "incremental": $INCREMENTAL,
  "encrypted": $ENCRYPT_BACKUP,
  "retention_days": $RETENTION_DAYS,
  "purge_date": "$(date -d "+$RETENTION_DAYS days" +%Y-%m-%d)",
  "checksum": "$(sha256sum "$OUTPUT_FILE" | cut -d' ' -f1)",
  "record_count": $(wc -l < "$OUTPUT_FILE")
}
EOF

    log "Retention metadata created: $metadata_file"
}

# Function to clean up old backups
cleanup_old_backups() {
    log "Cleaning up backups older than $RETENTION_DAYS days..."

    find "$OUTPUT_DIR" -name "bookings_*.${EXPORT_FORMAT}*" -type f -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    find "$OUTPUT_DIR" -name "bookings_*.meta" -type f -mtime +$RETENTION_DAYS -delete 2>/dev/null || true

    log "Old backup cleanup completed"
}

# Main execution
log "Starting booking data backup for Sibali.id"
log "Date range: $DATE_FROM to $DATE_TO"
log "Format: $EXPORT_FORMAT"
log "Incremental: $INCREMENTAL"
log "Encryption: $ENCRYPT_BACKUP"

test_db_connection

# Export data
case "$EXPORT_FORMAT" in
    csv)
        export_to_csv
        ;;
    json)
        export_to_json
        ;;
    *)
        log "ERROR: Unsupported format: $EXPORT_FORMAT"
        exit 1
        ;;
esac

encrypt_backup
generate_checksum
add_retention_metadata
cleanup_old_backups

log "Booking backup completed successfully"
log "Output file: $OUTPUT_FILE"
log "Log file: $LOG_FILE"

echo "Backup Summary:"
echo "- File: $OUTPUT_FILE"
echo "- Format: $EXPORT_FORMAT"
echo "- Date Range: $DATE_FROM to $DATE_TO"
echo "- Incremental: $INCREMENTAL"
echo "- Encrypted: $ENCRYPT_BACKUP"
echo "- Retention: $RETENTION_DAYS days"
echo "- Log: $LOG_FILE"
