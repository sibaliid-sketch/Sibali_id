# Runbook Troubleshooting Pasca-Deployment

## Incident Severity Classification

### Severity 1 (Critical)
- **Impact**: Complete system outage, data loss, security breach
- **Response Time**: Immediate (within 15 minutes)
- **Resolution Time**: Within 1 hour
- **Communication**: All stakeholders notified immediately

### Severity 2 (High)
- **Impact**: Major functionality broken, significant user impact
- **Response Time**: Within 30 minutes
- **Resolution Time**: Within 4 hours
- **Communication**: Engineering team and key stakeholders

### Severity 3 (Medium)
- **Impact**: Minor functionality issues, partial degradation
- **Response Time**: Within 2 hours
- **Resolution Time**: Within 24 hours
- **Communication**: Engineering team lead

## Masalah Umum dan Resolusi

### 1. Application Unresponsive (Severity 1)

#### Symptoms
- HTTP 502/503/504 errors
- White screen of death
- Application completely inaccessible
- High error rates in monitoring

#### Diagnostic Commands
```bash
# Check application status
curl -I https://www.sibali.id

# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Check web server status
sudo systemctl status nginx

# Check application logs
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log

# Check system resources
htop
df -h
free -h
```

#### Resolution Steps
1. **Immediate Assessment**
   ```bash
   # Check if services are running
   sudo systemctl status nginx php8.1-fpm mysql redis
   ```

2. **Restart Services**
   ```bash
   # Restart PHP-FPM
   sudo systemctl restart php8.1-fpm

   # Restart web server
   sudo systemctl restart nginx

   # Clear application cache
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Check Database Connectivity**
   ```bash
   # Test database connection
   php artisan tinker --execute="DB::connection()->getPdo()"
   ```

#### Escalation
- If issue persists after 10 minutes: Page SRE on-call
- If database connectivity fails: Contact DBA immediately
- If infrastructure issue suspected: Contact DevOps team

### 2. Slow Response Times (Severity 2)

#### Symptoms
- Page load times > 5 seconds
- API response times > 2 seconds
- Increased CPU/memory usage
- Queue backlog growing

#### Diagnostic Commands
```bash
# Check response times
curl -o /dev/null -s -w "%{time_total}\n" https://www.sibali.id

# Monitor system resources
sar -u 1 5
sar -r 1 5

# Check database slow queries
mysql -e "SHOW PROCESSLIST"

# Check Redis performance
redis-cli --latency

# Check queue status
php artisan queue:status
```

#### Resolution Steps
1. **Identify Bottleneck**
   ```bash
   # Check database connections
   mysql -e "SHOW STATUS LIKE 'Threads_connected'"

   # Check Redis memory usage
   redis-cli info memory

   # Check PHP-FPM pool status
   sudo systemctl status php8.1-fpm
   ```

2. **Optimize Performance**
   ```bash
   # Clear caches
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

   # Restart queue workers
   php artisan queue:restart

   # Optimize database
   php artisan db:monitor
   ```

3. **Scale Resources if Needed**
   ```bash
   # Horizontal scaling (if using Kubernetes)
   kubectl scale deployment sibali-app --replicas=5

   # Vertical scaling (if using traditional hosting)
   # Contact infrastructure team for server upgrade
   ```

#### Log Snippets
```
[2024-01-15 10:30:15] production.ERROR: SQLSTATE[HY000] [2002] Connection timed out
[2024-01-15 10:30:16] production.WARNING: Queue worker timeout after 60 seconds
[2024-01-15 10:30:20] production.ERROR: Redis connection failed: Connection refused
```

### 3. Database Connection Issues (Severity 2)

#### Symptoms
- "SQLSTATE[HY000] [2002] Connection refused"
- Database connection timeout errors
- Application partially functional
- User login/registration failing

#### Diagnostic Commands
```bash
# Check MySQL status
sudo systemctl status mysql

# Check MySQL connections
mysql -e "SHOW PROCESSLIST"
mysql -e "SHOW STATUS LIKE 'Threads_connected'"
mysql -e "SHOW STATUS LIKE 'Max_used_connections'"

# Test database connectivity
mysql -h prod-db.sibali.id -u u486134328_sibali -p u486134328_sibaliid -e "SELECT 1"

# Check database logs
tail -f /var/log/mysql/error.log
```

#### Resolution Steps
1. **Check Connection Limits**
   ```bash
   # View current connections
   mysql -e "SHOW VARIABLES LIKE 'max_connections'"

   # Check connection usage
   mysql -e "SHOW STATUS LIKE 'Threads_connected'"
   ```

2. **Restart Database Service**
   ```bash
   sudo systemctl restart mysql
   ```

3. **Optimize Connection Pool**
   ```php
   // In database.php config
   'connections' => [
       'mysql' => [
           'host' => env('DB_HOST'),
           'port' => env('DB_PORT'),
           'database' => env('DB_DATABASE'),
           'username' => env('DB_USERNAME'),
           'password' => env('DB_PASSWORD'),
           'options' => [
               PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
               PDO::ATTR_PERSISTENT => true,
           ],
       ],
   ],
   ```

#### Log Snippets
```
[2024-01-15 11:45:20] production.ERROR: PDOException: SQLSTATE[08006] [7] FATAL: remaining connection slots are reserved for non-replication superuser connections
[2024-01-15 11:45:25] production.ERROR: SQLSTATE[HY000] [2002] Connection refused (Connection refused)
[2024-01-15 11:46:00] production.WARNING: Database connection timeout after 30 seconds
```

### 4. Queue Worker Failures (Severity 3)

#### Symptoms
- Email notifications not sending
- Background jobs not processing
- Queue backlog increasing
- Scheduled tasks not running

#### Diagnostic Commands
```bash
# Check queue status
php artisan queue:status

# Check failed jobs
php artisan queue:failed

# Check supervisor status
sudo supervisorctl status

# Check queue worker logs
tail -f storage/logs/worker.log

# Check Redis queue length
redis-cli LLEN queues:default
```

#### Resolution Steps
1. **Restart Queue Workers**
   ```bash
   # Using supervisor
   sudo supervisorctl restart laravel-worker:*

   # Manual restart
   php artisan queue:restart
   ```

2. **Clear Failed Jobs**
   ```bash
   # View failed jobs
   php artisan queue:failed

   # Retry failed jobs
   php artisan queue:retry all

   # Clear old failed jobs
   php artisan queue:forget {id}
   ```

3. **Check Queue Configuration**
   ```php
   // In queue.php config
   'connections' => [
       'redis' => [
           'driver' => 'redis',
           'connection' => 'default',
           'queue' => env('REDIS_QUEUE', 'default'),
           'retry_after' => 90,
           'block_for' => null,
       ],
   ],
   ```

#### Log Snippets
```
[2024-01-15 12:00:00] production.ERROR: Queue worker failed: Job timed out after 60 seconds
[2024-01-15 12:00:05] production.WARNING: Failed to send email notification: Connection timeout
[2024-01-15 12:00:10] production.ERROR: Redis connection lost during job processing
```

### 5. Cache Issues (Severity 3)

#### Symptoms
- Stale data displayed
- Slow page loads due to cache misses
- High Redis memory usage
- Cache connection errors

#### Diagnostic Commands
```bash
# Check Redis status
redis-cli ping
redis-cli info memory
redis-cli info stats

# Check cache hit/miss ratio
redis-cli info stats | grep keyspace

# Check application cache
php artisan cache:clear
php artisan cache:status

# Monitor cache keys
redis-cli keys "sibali:*"
```

#### Resolution Steps
1. **Clear Application Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. **Restart Cache Service**
   ```bash
   sudo systemctl restart redis
   ```

3. **Optimize Cache Configuration**
   ```php
   // In cache.php config
   'stores' => [
       'redis' => [
           'driver' => 'redis',
           'connection' => 'cache',
           'lock_connection' => 'default',
       ],
   ],
   ```

#### Log Snippets
```
[2024-01-15 13:15:30] production.WARNING: Cache connection failed, falling back to file cache
[2024-01-15 13:15:35] production.ERROR: Redis memory limit exceeded
[2024-01-15 13:16:00] production.WARNING: Cache key not found, database query executed
```

### 6. File Upload Issues (Severity 3)

#### Symptoms
- File upload failures
- Image display issues
- Storage permission errors
- Large file upload timeouts

#### Diagnostic Commands
```bash
# Check storage permissions
ls -la storage/
ls -la storage/app/public/

# Check disk space
df -h

# Check PHP upload settings
php -r "echo ini_get('upload_max_filesize');"
php -r "echo ini_get('post_max_size');"

# Check storage link
php artisan storage:link
```

#### Resolution Steps
1. **Fix Storage Permissions**
   ```bash
   # Set correct permissions
   chown -R www-data:www-data storage/
   chmod -R 755 storage/
   chmod -R 775 storage/app/public/
   ```

2. **Update PHP Configuration**
   ```ini
   ; In php.ini
   upload_max_filesize = 50M
   post_max_size = 50M
   max_execution_time = 300
   memory_limit = 256M
   ```

3. **Check Storage Configuration**
   ```php
   // In filesystems.php config
   'disks' => [
       'public' => [
           'driver' => 'local',
           'root' => storage_path('app/public'),
           'url' => env('APP_URL').'/storage',
           'visibility' => 'public',
       ],
   ],
   ```

#### Log Snippets
```
[2024-01-15 14:20:15] production.ERROR: Unable to write file to disk: Permission denied
[2024-01-15 14:20:20] production.WARNING: File upload size exceeds limit
[2024-01-15 14:20:25] production.ERROR: Storage link not found
```

## Error Pages Mapping

### HTTP Status Code Mapping
- **400 Bad Request**: Invalid request parameters
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied
- **404 Not Found**: Resource not found
- **408 Request Timeout**: Request took too long
- **413 Payload Too Large**: File upload too big
- **422 Unprocessable Entity**: Validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Application error
- **502 Bad Gateway**: Gateway error
- **503 Service Unavailable**: Service temporarily down
- **504 Gateway Timeout**: Gateway timeout

### Custom Error Pages
```php
// In app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
        return response()->view('errors.401', [], 401);
    }

    if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
        return response()->view('errors.403', [], 403);
    }

    if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        return response()->view('errors.404', [], 404);
    }

    return parent::render($request, $exception);
}
```

## Lokasi dan Rotasi Path Log

### Application Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Worker Logs**: `storage/logs/worker.log`
- **Queue Logs**: `storage/logs/queue.log`

### Infrastructure Logs
- **Nginx Access**: `/var/log/nginx/access.log`
- **Nginx Error**: `/var/log/nginx/error.log`
- **PHP-FPM**: `/var/log/php8.1-fpm.log`
- **MySQL**: `/var/log/mysql/mysql.log`
- **Redis**: `/var/log/redis/redis.log`
- **System**: `/var/log/syslog`

### Log Rotation Configuration
```bash
# Laravel log rotation (logrotate.d/laravel)
/var/www/sibali.id/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        /usr/sbin/service php8.1-fpm reload
    endscript
}

# Nginx log rotation
/var/log/nginx/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data adm
    postrotate
        /usr/sbin/service nginx reload
    endscript
}
```

## Quick Fixes

### Cache Clear Commands
```bash
# Complete cache clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# OpCache reset
php artisan opcache:clear

# CDN cache purge (if applicable)
curl -X PURGE https://www.sibali.id/*
```

### Service Restart Commands
```bash
# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo systemctl restart redis

# Restart queue workers
sudo supervisorctl restart all
```

### Database Quick Fixes
```bash
# Check and repair tables
mysqlcheck -u root -p --repair --databases sibali

# Optimize tables
mysql -e "OPTIMIZE TABLE users, courses, enrollments;"

# Reset auto-increment
mysql -e "ALTER TABLE users AUTO_INCREMENT = 1;"
```

## How-to Roll Back Release

### Automated Rollback
```bash
# Using deployment script
./deploy/scripts/rollback.sh --release=v1.2.3

# Kubernetes rollback
kubectl rollout undo deployment/sibali-app

# Docker rollback
docker tag sibali-app:v1.2.2 sibali-app:latest
docker-compose up -d --no-deps sibali-app
```

### Manual Rollback Steps
1. **Stop current deployment**
   ```bash
   kubectl scale deployment sibali-app --replicas=0
   ```

2. **Deploy previous version**
   ```bash
   kubectl set image deployment/sibali-app sibali-app=sibali-app:v1.2.2
   kubectl scale deployment sibali-app --replicas=3
   ```

3. **Verify rollback**
   ```bash
   kubectl rollout status deployment/sibali-app
   curl -f https://www.sibali.id/health
   ```

### Database Rollback
```bash
# Rollback migrations
php artisan migrate:rollback --step=1

# Or rollback to specific migration
php artisan migrate:rollback --path=database/migrations/2024_01_15_000000_add_new_feature.php

# Restore from backup if needed
./deploy/scripts/restore.sh --backup=2024-01-15-pre-deploy.sql
```

## Escalation Path

### Severity 1 Incidents
1. **0-15 minutes**: On-call SRE responds
2. **15-30 minutes**: Engineering Manager notified
3. **30-60 minutes**: CTO/CEO notified if unresolved
4. **60+ minutes**: Customer communication initiated

### Severity 2 Incidents
1. **0-30 minutes**: Lead Developer responds
2. **30-120 minutes**: Engineering Manager notified
3. **120-240 minutes**: CTO notified if unresolved

### Severity 3 Incidents
1. **0-2 hours**: Assigned developer responds
2. **2-12 hours**: Team Lead notified
3. **12-24 hours**: Engineering Manager notified if unresolved

## Runbook for Degraded DB/Queue/Backpressure

### Database Degradation
#### Symptoms
- Slow query response times
- Connection pool exhausted
- High CPU usage on DB server

#### Remediation
```bash
# 1. Check slow queries
mysql -e "SHOW PROCESSLIST" | grep -v "Sleep"

# 2. Kill long-running queries
mysql -e "KILL QUERY 12345;"

# 3. Add missing indexes
php artisan db:monitor

# 4. Scale database (if using RDS)
aws rds modify-db-instance --db-instance-identifier sibali-db --apply-immediately --db-instance-class db.r5.large
```

### Queue Backpressure
#### Symptoms
- Queue length > 1000 jobs
- Worker processes at 100% CPU
- Memory usage increasing

#### Remediation
```bash
# 1. Check queue status
php artisan queue:status

# 2. Scale workers
sudo supervisorctl start laravel-worker:worker4
sudo supervisorctl start laravel-worker:worker5

# 3. Clear failed jobs
php artisan queue:retry all

# 4. Implement circuit breaker
php artisan queue:work --timeout=60 --tries=3 --memory=128 --sleep=3
```

### High Memory Usage
#### Symptoms
- PHP-FPM processes using > 100MB each
- System memory > 85% usage
- OOM killer activating

#### Remediation
```bash
# 1. Check memory usage
ps aux --sort=-%mem | head -10

# 2. Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# 3. Clear caches
php artisan cache:clear
php artisan config:clear

# 4. Optimize PHP settings
# pm.max_children = 50
# pm.max_requests = 500
# memory_limit = 128M
```

## SLO/SLA Impact Assessment

### Service Level Objectives (SLOs)
- **Availability**: 99.9% uptime (8.77 hours downtime/year)
- **Latency**: P95 < 500ms for API calls
- **Error Rate**: < 0.1% of requests
- **Throughput**: Handle 1000 concurrent users

### Impact Assessment Steps
1. **Quantify Impact**
   - Number of affected users
   - Duration of outage
   - Business revenue impact

2. **Calculate SLO Violation**
   ```bash
   # Calculate availability
   uptime_percentage = (total_time - downtime) / total_time * 100

   # Calculate error budget
   error_budget_used = (errors / total_requests) * 100
   ```

3. **Document Incident**
   - Timeline of events
   - Root cause analysis
   - Resolution steps
   - Prevention measures

4. **Post-Mortem Review**
   - What went wrong?
   - What went well?
   - What can be improved?
   - Action items assigned

## Kontak Darurat

### Internal Contacts
- **SRE On-Call**: +62 812-3456-7890 (WhatsApp/SMS)
- **Engineering Manager**: +62 811-2345-6789
- **CTO**: +62 810-1234-5678
- **DevOps Lead**: devops@sibali.id

### External Contacts
- **Hosting Provider (Hostinger)**: support@hostinger.com | +1-888-555-1234
- **Database Provider**: If applicable
- **CDN Provider**: If applicable
- **Payment Gateway**: support@midtrans.com

### Emergency Communication
- **Slack Channel**: #incidents
- **PagerDuty**: Integrated with on-call rotation
- **Status Page**: status.sibali.id
- **Customer Communication**: cs@sibali.id

## Monitoring Dashboard

### Key Metrics to Monitor
- Application response time
- Error rate percentage
- Database connection count
- Queue length
- Memory/CPU usage
- Disk space availability

### Alert Thresholds
- Response time > 2s: Warning
- Error rate > 1%: Critical
- DB connections > 80% of max: Warning
- Queue length > 500: Critical
- Memory usage > 85%: Warning
- Disk usage > 90%: Critical

### Monitoring Tools
- **Application**: Laravel Telescope, New Relic
- **Infrastructure**: Prometheus, Grafana
- **Database**: MySQL Enterprise Monitor
- **External**: Pingdom, UptimeRobot
