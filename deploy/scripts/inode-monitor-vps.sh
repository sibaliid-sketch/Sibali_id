#!/bin/bash
# inode-monitor.sh for VPS
# Monitors inode usage, sends alerts if above threshold

PROJECT_ROOT="/var/www/sibali/current"
LOG_DIR="/var/log/sibali"
THRESHOLD=90  # Percentage
EMAIL_ADMIN="{EMAIL_ADMIN}"
BOT_TOKEN="{BOT_TOKEN}"
CHAT_ID="{CHAT_ID}"
NOTIF_LOG="$LOG_DIR/notif.log"

# Rate limit: don't send more than once per hour
if [ -f "$NOTIF_LOG" ] && [ $(find "$NOTIF_LOG" -mmin -60 2>/dev/null | wc -l) -gt 0 ]; then
    exit 0
fi

# Check inode usage
USAGE=$(df -i "$PROJECT_ROOT" | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$USAGE" -gt "$THRESHOLD" ]; then
    MESSAGE="ALERT: Inode usage at $USAGE% on sibali.id (VPS). Run cleanup immediately."

    # Email notification
    echo "$MESSAGE" | mail -s "Inode Alert - sibali.id" "$EMAIL_ADMIN" 2>/dev/null || echo "Email failed"

    # Telegram notification
    curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/sendMessage" -d "chat_id=$CHAT_ID&text=$MESSAGE" >/dev/null

    # Log notification
    echo "$(date): Alert sent - $USAGE%" >> "$NOTIF_LOG"
fi
