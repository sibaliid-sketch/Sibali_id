#!/bin/bash
# inode-audit.sh for VPS
# Safe audit script: counts inodes without modifying files

PROJECT_ROOT="/var/www/sibali/current"
LOG_DIR="/var/log/sibali"  # Ensure this directory exists and is writable
DATE=$(date +%Y%m%d)
REPORT_CSV="$LOG_DIR/inode-audit-$DATE.csv"
REPORT_TXT="$LOG_DIR/inode-audit-$DATE.txt"

# Ensure log directory exists
sudo mkdir -p "$LOG_DIR"
sudo chown www-data:www-data "$LOG_DIR"  # Adjust user if needed

# Global inode usage
echo "Global inode usage for $PROJECT_ROOT:" > "$REPORT_TXT"
df -i "$PROJECT_ROOT" >> "$REPORT_TXT"
echo "" >> "$REPORT_TXT"

# Top 30 directories by direct file count (approximates inode usage per folder)
echo "Top 30 directories by direct file count (inodes):" >> "$REPORT_TXT"
cd "$PROJECT_ROOT" || exit 1
find . -type d -exec sh -c 'echo "$1: $(find "$1" -maxdepth 1 -type f | wc -l)"' _ {} \; | sort -k2 -nr | head -30 >> "$REPORT_TXT"

# CSV report
echo "Directory,FileCount" > "$REPORT_CSV"
find . -type d -exec sh -c 'echo "$1,$(find "$1" -maxdepth 1 -type f | wc -l)"' _ {} \; | sort -k2 -nr | head -30 >> "$REPORT_CSV"

echo "Audit completed. Reports: $REPORT_CSV (CSV) and $REPORT_TXT (human-readable)"
