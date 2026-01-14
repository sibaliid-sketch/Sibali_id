<?php

return [
    'authentication' => [
        'multi_factor' => [
            'enabled' => true,
            'methods' => ['sms', 'email', 'app'],
            'required_for' => ['admin', 'instructor'],
        ],
        'password_policy' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
            'prevent_reuse' => true,
            'max_age' => 90, // days
        ],
        'session_management' => [
            'timeout' => 3600, // seconds
            'regenerate_on_login' => true,
            'concurrent_sessions' => 3,
        ],
    ],
    'authorization' => [
        'role_based_access' => [
            'roles' => ['student', 'instructor', 'admin', 'super_admin'],
            'permissions' => [
                'create_course' => ['instructor', 'admin'],
                'edit_course' => ['instructor', 'admin'],
                'delete_course' => ['admin'],
                'manage_users' => ['admin', 'super_admin'],
            ],
        ],
        'resource_permissions' => [
            'courses' => ['read', 'write', 'delete'],
            'users' => ['read', 'write', 'delete'],
            'reports' => ['read'],
        ],
    ],
    'data_protection' => [
        'encryption' => [
            'at_rest' => true,
            'in_transit' => true,
            'algorithm' => 'AES-256-GCM',
        ],
        'anonymization' => [
            'user_data' => true,
            'logs' => false,
        ],
        'retention_policies' => [
            'user_data' => 2555, // days (7 years)
            'logs' => 365,
            'backups' => 2555,
        ],
    ],
    'threat_detection' => [
        'rate_limiting' => [
            'login_attempts' => 5,
            'api_calls' => 1000, // per hour
            'file_uploads' => 10, // per hour
        ],
        'intrusion_detection' => [
            'enabled' => false,
            'rules' => ['sql_injection', 'xss', 'csrf'],
        ],
        'anomaly_detection' => [
            'login_patterns' => true,
            'traffic_spikes' => true,
        ],
    ],
    'compliance' => [
        'gdpr' => [
            'data_subject_rights' => true,
            'consent_management' => true,
            'data_portability' => true,
        ],
        'audit_logging' => [
            'enabled' => true,
            'events' => ['login', 'data_access', 'changes'],
        ],
    ],
];
