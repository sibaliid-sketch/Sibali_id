# Panduan Lengkap Deployment untuk Environment Staging dan Production

## Urutan Pipeline Deployment

### 1. Pre-Deployment Checklist
- [ ] Code review completed and approved
- [ ] Unit tests passing (coverage > 80%)
- [ ] Integration tests passing
- [ ] Security scan passed (no critical vulnerabilities)
- [ ] Performance benchmarks met
- [ ] Database migrations tested on staging
- [ ] Feature flags configured appropriately
- [ ] Rollback plan documented

### 2. Environment Preparation
- [ ] Infrastructure health check (CPU < 70%, Memory < 80%, Disk < 85%)
- [ ] Database backup completed
- [ ] CDN cache warming initiated
- [ ] Monitoring alerts configured
- [ ] SSL certificates valid (> 30 days remaining)

### 3. Deployment Pipeline Steps

#### Staging Environment
```bash
# 1. Code checkout and build
git checkout develop
git pull origin develop
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Database operations
php artisan migrate --force
php artisan db:seed --class=StagingSeeder

# 3. Cache operations
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Queue and scheduler
php artisan queue:restart
php artisan schedule:run

# 5. Health checks
curl -f https://staging.sibali.id/health || exit 1
```

#### Production Environment
```bash
# 1. Blue-green deployment preparation
kubectl set image deployment/sibali-app sibali-app=sibali-app:v$(date +%Y%m%d-%H%M%S)

# 2. Wait for rollout
kubectl rollout status deployment/sibali-app

# 3. Health verification
kubectl exec -it deployment/sibali-app -- php artisan health:check

# 4. Traffic switch (if using load balancer)
# Update load balancer to route to new deployment

# 5. Post-deployment verification
curl -f https://www.sibali.id/health || kubectl rollout undo deployment/sibali-app
```

## Role Approvals

### Code Owner Approval
- **Required for**: Database schema changes, API modifications, security-related code
- **Approvers**: Tech Lead, Senior Backend Developer
- **Timeline**: Within 24 hours of PR creation

### QA Approval
- **Required for**: All deployments
- **Approvers**: QA Lead, assigned QA Engineer
- **Checklist**:
  - [ ] Functional testing completed
  - [ ] Regression testing passed
  - [ ] Performance testing results reviewed
  - [ ] User acceptance testing signed off

### Security Approval
- **Required for**: Authentication changes, data handling modifications
- **Approvers**: Security Officer, DevSecOps Engineer
- **Tools**: Snyk, SonarQube, manual security review

## Variable Environment

### Staging Environment Variables
```bash
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.sibali.id

DB_CONNECTION=mysql
DB_HOST=staging-db.sibali.id
DB_PORT=3306
DB_DATABASE=sibali_staging
DB_USERNAME=sibali_staging_user
DB_PASSWORD=${STAGING_DB_PASSWORD}

REDIS_HOST=staging-redis.sibali.id
REDIS_PASSWORD=${STAGING_REDIS_PASSWORD}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=staging@sibali.id
MAIL_PASSWORD=${STAGING_MAIL_PASSWORD}

GOOGLE_RECAPTCHA_SECRET=${STAGING_RECAPTCHA_SECRET}
PAYMENT_GATEWAY_MIDTRANS_SERVER_KEY=${STAGING_MIDTRANS_SERVER_KEY}
```

### Production Environment Variables
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.sibali.id

DB_CONNECTION=mysql
DB_HOST=prod-db.sibali.id
DB_PORT=3306
DB_DATABASE=u486134328_sibaliid
DB_USERNAME=u486134328_sibali
DB_PASSWORD=${PROD_DB_PASSWORD}

REDIS_HOST=prod-redis.sibali.id
REDIS_PASSWORD=${PROD_REDIS_PASSWORD}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=cs@sibali.id
MAIL_PASSWORD=${PROD_MAIL_PASSWORD}

GOOGLE_RECAPTCHA_SECRET=${PROD_RECAPTCHA_SECRET}
PAYMENT_GATEWAY_MIDTRANS_SERVER_KEY=${PROD_MIDTRANS_SERVER_KEY}
```

## Perintah Deploy Lokal

### Development Environment
```bash
# Quick deploy for development
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan storage:link
```

### Local Production Simulation
```bash
# Simulate production build locally
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Contoh Commit Message

### Format Standar
```
<type>(<scope>): <subject>

<body>

<footer>
```

### Contoh Commit Messages

#### Feature Implementation
```
feat(lms): add course enrollment functionality

- Implement course enrollment API endpoint
- Add enrollment validation rules
- Update course capacity tracking
- Add enrollment confirmation email

Closes #123
```

#### Bug Fix
```
fix(auth): resolve password reset token expiration

- Extend token validity to 24 hours
- Add token cleanup job
- Update password reset email template

Fixes #456
```

#### Security Update
```
security(auth): implement rate limiting on login attempts

- Add 5 attempts per minute limit
- Implement exponential backoff
- Log suspicious activities
- Send security alerts to admin

BREAKING CHANGE: Login API now returns 429 on rate limit
```

#### Performance Improvement
```
perf(cache): optimize user dashboard loading

- Implement Redis caching for user stats
- Add cache warming job
- Reduce database queries by 60%
- Improve response time from 2.5s to 0.8s
```

## Checklist Pra-Deploy

### Code Quality
- [ ] PSR-12 coding standards followed
- [ ] Code coverage > 80%
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Accessibility standards met (WCAG 2.1 AA)

### Security
- [ ] No hardcoded secrets
- [ ] Input validation implemented
- [ ] SQL injection prevention in place
- [ ] XSS protection enabled
- [ ] CSRF protection configured

### Performance
- [ ] Page load time < 2.5 seconds
- [ ] Core Web Vitals scores acceptable
- [ ] Database queries optimized
- [ ] Assets properly compressed and cached

### Functionality
- [ ] All user stories implemented
- [ ] Edge cases handled
- [ ] Error handling implemented
- [ ] Logging configured appropriately

## Strategi Zero-Downtime Deployment

### Blue-Green Deployment
```yaml
# Kubernetes deployment example
apiVersion: apps/v1
kind: Deployment
metadata:
  name: sibali-app-green
spec:
  replicas: 3
  selector:
    matchLabels:
      app: sibali-app
      version: green
  template:
    metadata:
      labels:
        app: sibali-app
        version: green
    spec:
      containers:
      - name: sibali-app
        image: sibali-app:v1.2.3
        ports:
        - containerPort: 80
        readinessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
```

### Rolling Update Strategy
```yaml
strategy:
  type: RollingUpdate
  rollingUpdate:
    maxUnavailable: 1
    maxSurge: 1
```

### Health Checks Configuration
```php
// Health check endpoint
Route::get('/health', function () {
    // Check database connection
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        return response('Database unhealthy', 500);
    }

    // Check Redis connection
    try {
        Redis::ping();
    } catch (\Exception $e) {
        return response('Cache unhealthy', 500);
    }

    // Check queue health
    $failedJobs = DB::table('failed_jobs')->count();
    if ($failedJobs > 100) {
        return response('Queue unhealthy', 500);
    }

    return response('OK', 200);
});
```

## Database Migration Order

### Migration Strategy
1. **Pre-deployment migrations**: Schema changes that can run with old code
2. **Zero-downtime migrations**: Additive changes only
3. **Post-deployment migrations**: Breaking changes after full rollout

### Backward-Compatible Migration Pattern
```php
// Migration example with backward compatibility
Schema::table('users', function (Blueprint $table) {
    // Add new column with default value
    $table->boolean('email_verified')->default(false);

    // Add index for performance
    $table->index('email_verified');
});

// In application code, handle both old and new data
$user = User::find(1);
$emailVerified = $user->email_verified ?? false; // Backward compatible
```

### Migration Rollback Plan
```bash
# Rollback command
php artisan migrate:rollback --step=1

# Or specific migration
php artisan migrate:rollback --path=database/migrations/2023_01_01_000000_add_email_verified_to_users_table.php
```

## Required Approvals

### Deployment Approvals Matrix

| Environment | Code Owner | QA | Security | Ops | Business Owner |
|-------------|------------|----|----------|-----|----------------|
| Development | ✓ | - | - | - | - |
| Staging | ✓ | ✓ | ✓ | - | - |
| Production | ✓ | ✓ | ✓ | ✓ | ✓ |

### Approval Workflow
1. **Pull Request Created** → Code review requested
2. **Code Review Completed** → QA testing initiated
3. **QA Testing Passed** → Security review requested
4. **Security Review Passed** → Deployment approval requested
5. **Deployment Approved** → Automated deployment triggered

## Health Probes Configuration

### Readiness Probe
```yaml
readinessProbe:
  httpGet:
    path: /readiness
    port: 80
  initialDelaySeconds: 5
  periodSeconds: 5
  timeoutSeconds: 3
  failureThreshold: 3
```

### Liveness Probe
```yaml
livenessProbe:
  httpGet:
    path: /health
    port: 80
  initialDelaySeconds: 30
  periodSeconds: 30
  timeoutSeconds: 10
  failureThreshold: 3
```

### Startup Probe
```yaml
startupProbe:
  httpGet:
    path: /health
    port: 80
  initialDelaySeconds: 10
  periodSeconds: 10
  timeoutSeconds: 5
  failureThreshold: 30
```

## Smoke Tests

### Post-Deployment Smoke Test Script
```bash
#!/bin/bash
# smoke-test.sh

BASE_URL="https://www.sibali.id"
EXIT_CODE=0

echo "Running smoke tests..."

# Test homepage
if curl -f -s "$BASE_URL" > /dev/null; then
    echo "✓ Homepage accessible"
else
    echo "✗ Homepage failed"
    EXIT_CODE=1
fi

# Test API health
if curl -f -s "$BASE_URL/api/health" > /dev/null; then
    echo "✓ API health check passed"
else
    echo "✗ API health check failed"
    EXIT_CODE=1
fi

# Test database connection
if curl -f -s "$BASE_URL/api/db-check" > /dev/null; then
    echo "✓ Database connection OK"
else
    echo "✗ Database connection failed"
    EXIT_CODE=1
fi

# Test user authentication
if curl -f -s -X POST "$BASE_URL/api/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"test@sibali.id","password":"testpass"}' > /dev/null; then
    echo "✓ Authentication endpoint responding"
else
    echo "✗ Authentication failed"
    EXIT_CODE=1
fi

exit $EXIT_CODE
```

## Audit Trail dan Lokasi Log

### Application Logs
- **Location**: `storage/logs/laravel.log`
- **Rotation**: Daily rotation with 30-day retention
- **Format**: JSON structured logging
- **Monitoring**: ELK stack integration

### Infrastructure Logs
- **Web Server**: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **Database**: `/var/log/mysql/mysql.log`, `/var/log/mysql/error.log`
- **Cache**: Redis logs in `/var/log/redis/redis.log`
- **System**: `/var/log/syslog`, `/var/log/auth.log`

### Audit Logging
```php
// Audit logging implementation
Log::channel('audit')->info('User login', [
    'user_id' => $user->id,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'timestamp' => now(),
]);
```

## Contoh CLI Commands

### Deployment Commands
```bash
# Staging deployment
ansible-playbook -i inventory/staging deploy.yml --tags deploy

# Production deployment with confirmation
ansible-playbook -i inventory/production deploy.yml --tags deploy --extra-vars "confirm=yes"

# Rollback commands
ansible-playbook -i inventory/production rollback.yml --tags rollback
```

### Monitoring Commands
```bash
# Check application status
curl -s https://www.sibali.id/health | jq .

# Monitor queue status
php artisan queue:status

# Check database connections
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'Connected' : 'Failed'"

# View recent logs
tail -f storage/logs/laravel.log | jq .
```

### Environment Variable Matrix

| Variable | Development | Staging | Production | Description |
|----------|-------------|---------|------------|-------------|
| APP_ENV | local | staging | production | Application environment |
| APP_DEBUG | true | false | false | Debug mode enabled |
| APP_URL | http://localhost | https://staging.sibali.id | https://www.sibali.id | Base application URL |
| DB_HOST | 127.0.0.1 | staging-db.sibali.id | prod-db.sibali.id | Database host |
| DB_DATABASE | sibali_dev | sibali_staging | u486134328_sibaliid | Database name |
| REDIS_HOST | 127.0.0.1 | staging-redis.sibali.id | prod-redis.sibali.id | Redis host |
| MAIL_MAILER | log | smtp | smtp | Mail driver |
| CACHE_DRIVER | file | redis | redis | Cache driver |
| QUEUE_CONNECTION | sync | database | redis | Queue connection |
