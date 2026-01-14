<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Advanced caching strategies per module (LMS, CRM, Dashboard, Marketing).
    | Configures tier mapping, TTL defaults, warming, invalidation, and limits.
    |
    */

    'modules' => [
        'lms' => 'hot',
        'crm' => 'warm',
        'dashboard' => 'hot',
        'marketing' => 'cold',
    ],

    'tiers' => [
        'hot' => [
            'connection' => 'redis_hot',
            'ttl' => 3600, // 1 hour
        ],
        'warm' => [
            'connection' => 'redis_warm',
            'ttl' => 7200, // 2 hours
        ],
        'cold' => [
            'connection' => 'redis_cold',
            'ttl' => 86400, // 24 hours
        ],
    ],

    'ttl_defaults' => [
        'lms' => [
            'courses' => 1800,
            'lessons' => 3600,
            'user_progress' => 900,
        ],
        'crm' => [
            'leads' => 1800,
            'customers' => 3600,
            'interactions' => 900,
        ],
        'dashboard' => [
            'stats' => 300,
            'reports' => 1800,
        ],
        'marketing' => [
            'campaigns' => 3600,
            'analytics' => 7200,
        ],
    ],

    'cache_warming_endpoints' => [
        'lms' => [
            '/api/lms/courses/popular',
            '/api/lms/lessons/recent',
        ],
        'crm' => [
            '/api/crm/leads/active',
            '/api/crm/customers/top',
        ],
        'dashboard' => [
            '/api/dashboard/stats/overview',
        ],
        'marketing' => [
            '/api/marketing/campaigns/active',
        ],
    ],

    'stale_while_revalidate' => [
        'enabled' => true,
        'window' => 300, // 5 minutes
        'modules' => ['lms', 'crm'],
    ],

    'invalidation_events' => [
        'user_updated' => ['crm.customers', 'dashboard.stats'],
        'course_created' => ['lms.courses', 'lms.lessons'],
        'lead_converted' => ['crm.leads', 'crm.customers'],
        'campaign_ended' => ['marketing.campaigns'],
    ],

    'max_cached_items' => [
        'lms' => 10000,
        'crm' => 5000,
        'dashboard' => 1000,
        'marketing' => 2000,
    ],

    'cache_key_prefix' => [
        'local' => 'sibali_local',
        'staging' => 'sibali_staging',
        'production' => 'sibali_prod',
    ],
];
