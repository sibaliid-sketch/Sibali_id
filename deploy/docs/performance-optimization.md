# Teknik Tuning Performa untuk Aplikasi dan Infra

## Caching Layers Strategy

### Edge CDN Configuration
```nginx
# Nginx configuration for CDN integration
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|webp|avif|woff|woff2)$ {
    # Cache static assets for 1 year
    expires 1y;
    add_header Cache-Control "public, immutable";

    # Enable gzip compression
    gzip on;
    gzip_types text/css application/javascript image/svg+xml;

    # CORS headers for CDN
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, OPTIONS";
    add_header Access-Control-Allow-Headers "DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range";

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
}

# API responses caching
location /api/ {
    # Cache API responses for 5 minutes
    proxy_cache api_cache;
    proxy_cache_key "$scheme$request_method$host$request_uri";
    proxy_cache_valid 200 5m;
    proxy_cache_valid 404 1m;

    # Bypass cache for authenticated requests
    proxy_cache_bypass $http_authorization;
}
```

### App-Level Cache Implementation
```php
// Cache configuration in config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

// Cache usage examples
class UserService
{
    public function getUserProfile($userId)
    {
        $cacheKey = "user_profile:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            return User::with(['courses', 'progress'])->find($userId);
        });
    }

    public function updateUserProfile($userId, $data)
    {
        // Invalidate cache on update
        Cache::forget("user_profile:{$userId}");

        // Update database
        $user = User::find($userId);
        $user->update($data);

        // Cache new data
        Cache::put("user_profile:{$userId}", $user, 3600);

        return $user;
    }
}

// Cache warming job
class WarmUserCache implements ShouldQueue
{
    public function handle()
    {
        $activeUsers = User::where('last_login_at', '>', now()->subDays(30))
                          ->pluck('id');

        foreach ($activeUsers as $userId) {
            Cache::remember("user_profile:{$userId}", 3600, function () use ($userId) {
                return User::with(['courses', 'progress'])->find($userId);
            });
        }
    }
}
```

### TTL Rules and Cache Invalidation
```php
// Cache TTL configuration
class CacheConfig
{
    const TTL = [
        'user_profile' => 3600,      // 1 hour
        'course_list' => 1800,       // 30 minutes
        'dashboard_stats' => 300,    // 5 minutes
        'leaderboard' => 600,        // 10 minutes
        'static_content' => 86400,   // 24 hours
    ];

    // Cache key patterns
    const KEYS = [
        'user_profile' => 'user:profile:%s',
        'course_list' => 'courses:list:page:%s:filter:%s',
        'dashboard_stats' => 'dashboard:stats:user:%s',
        'leaderboard' => 'leaderboard:%s:period:%s',
    ];
}

// Cache invalidation strategies
class CacheInvalidation
{
    public static function invalidateUserData($userId)
    {
        $keys = [
            sprintf(CacheConfig::KEYS['user_profile'], $userId),
            sprintf(CacheConfig::KEYS['dashboard_stats'], $userId),
            'courses:list:*', // Invalidate course lists
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public static function invalidateCourseData($courseId)
    {
        // Invalidate course-specific caches
        Cache::forget("course:{$courseId}");
        Cache::forget("course:{$courseId}:enrollments");

        // Tag-based invalidation for related data
        Cache::tags(['courses'])->flush();
    }
}
```

## Database Indexing and Query Patterns

### Critical Database Indices
```sql
-- User table indices
CREATE INDEX idx_users_email ON users (email);
CREATE INDEX idx_users_role_status ON users (role, status);
CREATE INDEX idx_users_last_login ON users (last_login_at);
CREATE INDEX idx_users_created_at ON users (created_at DESC);

-- Course table indices
CREATE INDEX idx_courses_category_status ON courses (category_id, status);
CREATE INDEX idx_courses_instructor ON courses (instructor_id);
CREATE INDEX idx_courses_created_at ON courses (created_at DESC);

-- Enrollment table indices
CREATE INDEX idx_enrollments_user_course ON enrollments (user_id, course_id);
CREATE INDEX idx_enrollments_status_date ON enrollments (status, enrolled_at);
CREATE INDEX idx_enrollments_course_status ON enrollments (course_id, status);

-- Progress tracking indices
CREATE INDEX idx_progress_user_course_lesson ON progress (user_id, course_id, lesson_id);
CREATE INDEX idx_progress_completed_at ON progress (completed_at) WHERE completed_at IS NOT NULL;

-- Composite indices for complex queries
CREATE INDEX idx_user_course_progress ON progress (user_id, course_id, completed_at DESC);
CREATE INDEX idx_course_enrollment_stats ON enrollments (course_id, status, enrolled_at DESC);
```

### Index Maintenance Schedule
```bash
#!/bin/bash
# index-maintenance.sh

# Analyze table statistics weekly
mysql -e "ANALYZE TABLE users, courses, enrollments, progress;"

# Rebuild indices monthly
mysql -e "ALTER TABLE users ENGINE=InnoDB;"
mysql -e "ALTER TABLE courses ENGINE=InnoDB;"

# Check for unused indices quarterly
mysql -e "
SELECT
    object_schema,
    object_name,
    index_name,
    count_read,
    count_write,
    count_read / (count_read + count_write) * 100 as read_ratio
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = 'sibali'
    AND index_name IS NOT NULL
    AND count_read = 0
ORDER BY object_schema, object_name;
"

# Monitor index fragmentation
mysql -e "
SELECT
    table_name,
    index_name,
    avg_fragmentation_pct
FROM information_schema.statistics s
LEFT JOIN (
    SELECT
        table_name,
        index_name,
        avg_fragmentation_pct
    FROM sys.schema_index_statistics
) frag ON s.table_name = frag.table_name AND s.index_name = frag.index_name
WHERE s.table_schema = 'sibali';
"
```

### Query Optimization Patterns
```php
// Optimized query patterns
class OptimizedQueries
{
    // Use eager loading to prevent N+1 queries
    public function getUsersWithCourses()
    {
        return User::with(['courses' => function ($query) {
            $query->select('id', 'title', 'status')
                  ->where('status', 'active');
        }])
        ->where('status', 'active')
        ->get();
    }

    // Use database indexes effectively
    public function getRecentEnrollments($days = 30)
    {
        return DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->select('enrollments.*', 'users.name', 'courses.title')
            ->where('enrollments.enrolled_at', '>=', now()->subDays($days))
            ->where('enrollments.status', 'active')
            ->orderBy('enrollments.enrolled_at', 'desc')
            ->get();
    }

    // Use pagination for large datasets
    public function getCoursesPaginated($perPage = 20)
    {
        return Course::select('id', 'title', 'description', 'instructor_id', 'created_at')
            ->with(['instructor:id,name'])
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // Use raw queries for complex aggregations
    public function getDashboardStats()
    {
        return Cache::remember('dashboard_stats', 300, function () {
            return DB::select("
                SELECT
                    (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()) as new_users_today,
                    (SELECT COUNT(*) FROM enrollments WHERE enrolled_at >= CURDATE()) as enrollments_today,
                    (SELECT COUNT(*) FROM courses WHERE status = 'published') as active_courses,
                    (SELECT AVG(rating) FROM course_ratings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as avg_rating_30d
            ")[0];
        });
    }
}
```

## Queue Sizing and Backpressure Handling

### Queue Configuration
```php
// Queue configuration in config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],

// Queue worker configuration
'artisan queue:work' => [
    '--sleep' => 3,
    '--tries' => 3,
    '--max-jobs' => 1000,
    '--memory' => 128,
    '--timeout' => 60,
],
```

### Backpressure Handling
```php
// Queue monitoring and backpressure
class QueueMonitor
{
    const MAX_QUEUE_SIZE = 1000;
    const BACKPRESSURE_THRESHOLD = 800;

    public function checkQueueHealth()
    {
        $queueSize = Queue::size('default');

        if ($queueSize > self::MAX_QUEUE_SIZE) {
            // Trigger emergency measures
            $this->handleQueueOverflow();
        } elseif ($queueSize > self::BACKPRESSURE_THRESHOLD) {
            // Implement backpressure
            $this->implementBackpressure();
        }
    }

    private function handleQueueOverflow()
    {
        // Stop accepting new jobs
        Queue::pause();

        // Send alerts
        Notification::route('slack', env('SLACK_WEBHOOK'))
            ->notify(new QueueOverflowAlert());

        // Scale up workers
        $this->scaleWorkers();
    }

    private function implementBackpressure()
    {
        // Reduce job acceptance rate
        // Implement circuit breaker pattern
        $this->circuitBreaker->trip();

        // Send warnings
        Log::warning('Queue backpressure detected', [
            'queue_size' => Queue::size('default'),
            'threshold' => self::BACKPRESSURE_THRESHOLD
        ]);
    }

    private function scaleWorkers()
    {
        // Scale up queue workers
        if (app()->environment('production')) {
            // Use Kubernetes HPA or similar
            exec('kubectl scale deployment queue-worker --replicas=10');
        }
    }
}
```

### Queue Sizing Guidelines
```yaml
# Kubernetes deployment for queue workers
apiVersion: apps/v1
kind: Deployment
metadata:
  name: sibali-queue-worker
spec:
  replicas: 3
  selector:
    matchLabels:
      app: queue-worker
  template:
    metadata:
      labels:
        app: queue-worker
    spec:
      containers:
      - name: queue-worker
        image: sibali-app:latest
        command: ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90"]
        resources:
          requests:
            memory: "128Mi"
            cpu: "100m"
          limits:
            memory: "256Mi"
            cpu: "500m"
        env:
        - name: QUEUE_CONNECTION
          value: "redis"
        - name: REDIS_HOST
          value: "redis-service"
```

## Horizontal Scaling Guidance

### Load Balancer Configuration
```nginx
# Load balancer configuration
upstream backend {
    least_conn;
    server app1.sibali.id:80 weight=1 max_fails=3 fail_timeout=30s;
    server app2.sibali.id:80 weight=1 max_fails=3 fail_timeout=30s;
    server app3.sibali.id:80 weight=1 max_fails=3 fail_timeout=30s;
    keepalive 32;
}

server {
    listen 80;
    server_name www.sibali.id;

    location / {
        proxy_pass http://backend;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Health check
        health_check interval=10 fails=3 passes=2;
    }

    # Sticky sessions for stateful operations
    location /admin {
        proxy_pass http://backend;
        sticky cookie srv_id expires=1h;
    }
}
```

### Auto-scaling Rules
```yaml
# Kubernetes HorizontalPodAutoscaler
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: sibali-app-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: sibali-app
  minReplicas: 3
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
  - type: External
    external:
      metric:
        name: nginx_ingress_controller_requests_per_second
      target:
        type: Value
        value: 1000
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
      - type: Percent
        value: 10
        periodSeconds: 60
    scaleUp:
      stabilizationWindowSeconds: 60
      policies:
      - type: Percent
        value: 50
        periodSeconds: 60
```

### Database Read Replicas
```php
// Read/write connection configuration
'connections' => [
    'mysql' => [
        'write' => [
            'host' => env('DB_HOST'),
        ],
        'read' => [
            'host' => env('DB_READ_HOST', env('DB_HOST')),
        ],
        'sticky' => true,
        'driver' => 'mysql',
        // ... other config
    ],
],

// Usage in models
class Course extends Model
{
    // Force write connection for critical operations
    public function create(array $attributes = [])
    {
        return static::query()->useWriteConnection()->create($attributes);
    }

    // Use read connection for listings
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
```

## Profiling Tools and Process

### Application Profiling
```php
// Laravel Debugbar integration
class ProfilingService
{
    public static function startProfiling($name)
    {
        if (app()->environment('local')) {
            Debugbar::startMeasure($name, $name);
        }
    }

    public static function stopProfiling($name)
    {
        if (app()->environment('local')) {
            Debugbar::stopMeasure($name);
        }
    }

    public static function profileDatabase()
    {
        DB::listen(function ($query) {
            if ($query->time > 1000) { // Log slow queries
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                    'bindings' => $query->bindings,
                ]);
            }
        });
    }
}

// Usage in controllers
class CourseController extends Controller
{
    public function index()
    {
        ProfilingService::startProfiling('course_listing');

        $courses = Course::published()->paginate(20);

        ProfilingService::stopProfiling('course_listing');

        return view('courses.index', compact('courses'));
    }
}
```

### Infrastructure Profiling
```bash
# PHP profiling with Xdebug
# Install Xdebug and configure
zend_extension=xdebug.so
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%p

# Generate profiling data
php artisan route:list > /dev/null

# Analyze with webgrind or similar tool
# Access webgrind at http://localhost/webgrind

# Memory profiling
php -r "
$start = memory_get_usage();
$user = User::find(1);
$end = memory_get_usage();
echo 'Memory used: ' . ($end - $start) . ' bytes';
"
```

### Performance Baseline Recording
```php
// Performance baseline recording
class PerformanceBaseline
{
    public static function recordBaseline($testName, $metrics)
    {
        $baseline = [
            'test_name' => $testName,
            'timestamp' => now(),
            'metrics' => $metrics,
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        Storage::put(
            "baselines/{$testName}-" . date('Y-m-d-H-i-s') . '.json',
            json_encode($baseline, JSON_PRETTY_PRINT)
        );
    }

    public static function compareWithBaseline($testName, $currentMetrics)
    {
        $latestBaseline = collect(Storage::files('baselines'))
            ->filter(fn($file) => str_contains($file, $testName))
            ->sort()
            ->last();

        if (!$latestBaseline) {
            return null;
        }

        $baseline = json_decode(Storage::get($latestBaseline), true);

        return [
            'response_time_change' => $currentMetrics['response_time'] - $baseline['metrics']['response_time'],
            'memory_change' => $currentMetrics['memory_usage'] - $baseline['metrics']['memory_usage'],
            'query_count_change' => $currentMetrics['query_count'] - $baseline['metrics']['query_count'],
        ];
    }
}

// Performance test example
class PerformanceTest
{
    public function testCourseListing()
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $queryCount = 0;

        DB::listen(fn() => $queryCount++);

        // Execute test
        $response = $this->get('/courses');

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $metrics = [
            'response_time' => ($endTime - $startTime) * 1000, // ms
            'memory_usage' => $endMemory - $startMemory, // bytes
            'query_count' => $queryCount,
            'status_code' => $response->getStatusCode(),
        ];

        PerformanceBaseline::recordBaseline('course_listing', $metrics);

        return $metrics;
    }
}
```

## Implementation Checklist

### Caching Implementation
- [ ] Redis cache cluster configured
- [ ] CDN integration completed
- [ ] Cache warming jobs scheduled
- [ ] Cache invalidation strategy implemented
- [ ] Cache monitoring alerts configured

### Database Optimization
- [ ] All critical indices created
- [ ] Slow query log enabled
- [ ] Query optimization completed
- [ ] Connection pooling configured
- [ ] Read replicas set up

### Application Performance
- [ ] Code profiling tools integrated
- [ ] Performance baselines established
- [ ] Memory leaks identified and fixed
- [ ] N+1 query problems resolved
- [ ] Asset optimization completed

### Infrastructure Scaling
- [ ] Load balancer configured
- [ ] Auto-scaling rules defined
- [ ] Monitoring dashboards created
- [ ] Capacity planning completed
- [ ] Disaster recovery tested

### Monitoring and Alerting
- [ ] Performance metrics collected
- [ ] Alert thresholds configured
- [ ] Incident response procedures documented
- [ ] Performance regression tests automated

## Tools and Resources

### Profiling Tools
- **Blackfire**: PHP performance profiling
- **Tideways**: Application performance monitoring
- **XHProf**: Hierarchical profiler for PHP
- **Webgrind**: Xdebug profile analyzer
- **FlameGraph**: Visualization of profiled data

### Monitoring Tools
- **New Relic**: Application performance monitoring
- **Datadog**: Infrastructure and application monitoring
- **Prometheus**: Metrics collection and alerting
- **Grafana**: Metrics visualization
- **ELK Stack**: Log aggregation and analysis

### Database Tools
- **Percona Toolkit**: MySQL performance analysis
- **pt-query-digest**: Slow query analysis
- **mysqltuner**: MySQL configuration tuning
- **pgBadger**: PostgreSQL log analysis (if applicable)

### Load Testing Tools
- **Apache JMeter**: Load testing and performance measurement
- **Locust**: Scalable load testing
- **k6**: Modern load testing tool
- **Artillery**: Load testing and functional testing

### Best Practices Resources
- **Google Performance Best Practices**: Web performance guidelines
- **WebPageTest**: Real-world performance testing
- **Lighthouse**: Automated performance auditing
- **PageSpeed Insights**: Performance analysis and recommendations

## Performance Benchmarks

### Target Metrics
- **Response Time**: < 500ms for API calls, < 2s for page loads
- **Throughput**: 1000+ concurrent users
- **Error Rate**: < 0.1% under normal load
- **Availability**: 99.9% uptime
- **Database Query Time**: < 100ms average
- **Memory Usage**: < 128MB per request
- **CPU Usage**: < 70% under peak load

### Benchmarking Process
1. **Establish Baselines**: Record current performance metrics
2. **Load Testing**: Simulate realistic user traffic patterns
3. **Identify Bottlenecks**: Profile and analyze performance issues
4. **Optimize**: Implement performance improvements
5. **Re-test**: Verify improvements and check for regressions
6. **Monitor**: Set up continuous performance monitoring

### Capacity Planning
- **Current Load**: Analyze current usage patterns
- **Growth Projections**: Estimate future traffic growth
- **Resource Requirements**: Calculate needed infrastructure
- **Scaling Strategy**: Plan horizontal/vertical scaling approach
- **Cost Analysis**: Evaluate performance vs cost trade-offs
