<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firewall Configuration
    |--------------------------------------------------------------------------
    |
    | 20-Layer firewall configuration for app-level policy enforcement.
    | Enforces layered security rules including IP filtering, rate-limiting,
    | bot detection, and input sanitization.
    |
    */

    'layers' => [
        // Array of 20 layers with enabled flag
        ['name' => 'IP Filter', 'enabled' => true],
        ['name' => 'Rate Limiting', 'enabled' => true],
        ['name' => 'Bot Detection', 'enabled' => true],
        ['name' => 'Input Sanitization', 'enabled' => true],
        ['name' => 'SQL Injection Protection', 'enabled' => true],
        ['name' => 'XSS Filtering', 'enabled' => true],
        ['name' => 'CSRF Protection', 'enabled' => true],
        ['name' => 'Geo Blocking', 'enabled' => false],
        ['name' => 'User Agent Policy', 'enabled' => true],
        ['name' => 'Audit Logging', 'enabled' => true],
        // Add remaining 10 layers as needed
        ['name' => 'Layer 11', 'enabled' => false],
        ['name' => 'Layer 12', 'enabled' => false],
        ['name' => 'Layer 13', 'enabled' => false],
        ['name' => 'Layer 14', 'enabled' => false],
        ['name' => 'Layer 15', 'enabled' => false],
        ['name' => 'Layer 16', 'enabled' => false],
        ['name' => 'Layer 17', 'enabled' => false],
        ['name' => 'Layer 18', 'enabled' => false],
        ['name' => 'Layer 19', 'enabled' => false],
        ['name' => 'Layer 20', 'enabled' => false],
    ],

    'ip_whitelist' => [
        // CIDR list for allowed IPs
        '127.0.0.1/32',
        '192.168.1.0/24',
    ],

    'ip_blacklist' => [
        // CIDR list for blocked IPs
        '10.0.0.0/8',
    ],

    'rate_limits' => [
        // Route patterns → req/min, burst
        '/api/*' => ['req_per_min' => 100, 'burst' => 20],
        '/admin/*' => ['req_per_min' => 50, 'burst' => 10],
    ],

    'ua_policy' => [
        'allowed' => [
            // Allowed regex patterns
            '/Mozilla\/.*/',
        ],
        'blocked' => [
            // Blocked regex patterns
            '/curl\/.*/',
        ],
    ],

    'geo_blocking' => [
        'enabled' => false,
        'allowed_countries' => ['ID', 'US', 'SG'], // ISO country codes
    ],

    'bot_detection' => [
        'behavior_thresholds' => [
            'requests_per_second' => 10,
            'suspicious_patterns' => ['/admin', '/wp-admin'],
        ],
        'captcha_threshold' => 5,
    ],

    'sql_injection' => [
        'enabled' => true,
        'sanitizers' => ['strip_tags', 'htmlspecialchars'],
    ],

    'xss_filters' => [
        'enabled' => true,
        'exceptions' => ['/trusted/*'],
    ],

    'csrf_protection' => [
        'enabled' => true,
        'token_header' => 'X-CSRF-TOKEN',
    ],

    'audit_logging' => [
        'level' => 'info',
        'log_channel' => 'firewall',
    ],

    // Health: metrics per layer
    'health' => [
        'metrics_enabled' => true,
        'metrics_channel' => 'firewall_health',
    ],

    // Security: rules loaded from secure source, supports hot-reload, policy versioning, fallback deny
    'security' => [
        'rules_source' => 'secure_config',
        'hot_reload' => true,
        'policy_versioning' => true,
        'fallback_deny' => true,
    ],
];
