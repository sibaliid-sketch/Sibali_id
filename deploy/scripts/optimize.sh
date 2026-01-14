#!/bin/bash

# System Optimization Script for Sibali.id
# Performance optimization tasks: database, cache, system tuning

set -e

LOG_FILE="/var/log/sibali-optimize.log"
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

# Logging function
log() {
    echo "$TIMESTAMP - $1" | tee -a "$LOG_FILE"
}

log "Starting system optimization"

# Database optimization
log "Optimizing database tables"
mysql -h"${DB_HOST:-localhost}" -u"${DB_USER:-root}" -p"${DB_PASS}" "${DB_NAME:-u486134328_sibaliid}" << EOF
ANALYZE TABLE users;
ANALYZE TABLE products;
ANALYZE TABLE orders;
ANALYZE TABLE lms_enrollments;
ANALYZE TABLE crm_leads;
OPTIMIZE TABLE users;
OPTIMIZE TABLE products;
OPTIMIZE TABLE orders;
OPTIMIZE TABLE lms_enrollments;
OPTIMIZE TABLE crm_leads;
EOF

# Clear Laravel caches
log "Clearing Laravel caches"
cd /var/www/sibali
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Clear expired sessions and cache
log "Clearing expired sessions and cache"
find storage/framework/sessions -name "sess_*" -type f -mtime +1 -delete 2>/dev/null || true
find storage/framework/cache -name "*.php" -type f -mtime +7 -delete 2>/dev/null || true

# Optimize file permissions
log "Optimizing file permissions"
chown -R www-data:www-data /var/www/sibali/storage
chown -R www-data:www-data /var/www/sibali/bootstrap/cache
chmod -R 755 /var/www/sibali/storage
chmod -R 755 /var/www/sibali/bootstrap/cache

# Clear system caches (if using Redis)
if command -v redis-cli &> /dev/null; then
    log "Clearing Redis cache"
    redis-cli -n 0 FLUSHDB  # Clear default database
    redis-cli -n 1 FLUSHDB  # Clear cache database
fi

# Clear PHP OPcache (if available)
if php -r "echo function_exists('opcache_reset') ? 'yes' : 'no';" | grep -q "yes"; then
    log "Resetting PHP OPcache"
    php -r "opcache_reset();"
fi

# Optimize log files
log "Optimizing log files"
find /var/log -name "*.log" -type f -exec truncate -s 0 {} \; 2>/dev/null || true
find storage/logs -name "*.log" -type f -exec truncate -s 0 {} \; 2>/dev/null || true

# System maintenance
log "Running system maintenance"
# Clear package cache
apt-get clean 2>/dev/null || yum clean all 2>/dev/null || true

# Update file timestamps for cache busting (optional)
log "Updating asset timestamps"
find public/build -name "*.js" -o -name "*.css" -exec touch {} \; 2>/dev/null || true

# Run Laravel optimization commands
log "Running Laravel optimizations"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Generate sitemap if command exists
if php artisan | grep -q "sitemap:generate"; then
    log "Generating sitemap"
    php artisan sitemap:generate
fi

# Warm up critical caches
log "Warming up critical caches"
# Implement cache warming logic here
# Example: curl -s http://localhost/sitemap.xml > /dev/null

log "System optimization completed successfully"

# Optional: Send notification
# curl -X POST -H 'Content-type: application/json' --data '{"text":"System optimization completed"}' YOUR_SLACK_WEBHOOK
