<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Governance & Retention Rules
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for data lifecycle management and retention
    | policies. It enforces storage governance, automatic archiving, and cleanup
    | rules for different content types.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Retention Policies
    |--------------------------------------------------------------------------
    |
    | Retention policies for different content types.
    | Each policy defines hot/warm/cold storage tiers and retention periods.
    |
    */
    'retention_policies' => [
        'materials' => [
            'hot' => [
                'days' => env('STORAGE_MATERIALS_HOT_DAYS', 365), // 1 year
                'description' => 'Frequently accessed learning materials',
            ],
            'warm' => [
                'days' => env('STORAGE_MATERIALS_WARM_DAYS', 1095), // 3 years
                'description' => 'Occasionally accessed materials',
            ],
            'cold' => [
                'days' => env('STORAGE_MATERIALS_COLD_DAYS', 2555), // 7 years
                'description' => 'Rarely accessed archival materials',
            ],
        ],
        'user_uploads' => [
            'hot' => [
                'days' => env('STORAGE_UPLOADS_HOT_DAYS', 180), // 6 months
                'description' => 'Recent user uploads and documents',
            ],
            'warm' => [
                'days' => env('STORAGE_UPLOADS_WARM_DAYS', 730), // 2 years
                'description' => 'Older user documents',
            ],
            'cold' => [
                'days' => env('STORAGE_UPLOADS_COLD_DAYS', 1825), // 5 years
                'description' => 'Archival user data',
            ],
        ],
        'logs' => [
            'hot' => [
                'days' => env('STORAGE_LOGS_HOT_DAYS', 30), // 30 days
                'description' => 'Recent application logs',
            ],
            'warm' => [
                'days' => env('STORAGE_LOGS_WARM_DAYS', 90), // 90 days
                'description' => 'Older logs for analysis',
            ],
            'cold' => [
                'days' => env('STORAGE_LOGS_COLD_DAYS', 365), // 1 year
                'description' => 'Archival logs',
            ],
        ],
        'backups' => [
            'hot' => [
                'days' => env('STORAGE_BACKUPS_HOT_DAYS', 7), // 1 week
                'description' => 'Recent database and file backups',
            ],
            'warm' => [
                'days' => env('STORAGE_BACKUPS_WARM_DAYS', 30), // 30 days
                'description' => 'Monthly backups',
            ],
            'cold' => [
                'days' => env('STORAGE_BACKUPS_COLD_DAYS', 365), // 1 year
                'description' => 'Yearly archival backups',
            ],
        ],
        'temp_files' => [
            'hot' => [
                'days' => env('STORAGE_TEMP_HOT_DAYS', 1), // 1 day
                'description' => 'Temporary processing files',
            ],
            'warm' => [
                'days' => env('STORAGE_TEMP_WARM_DAYS', 7), // 1 week
                'description' => 'Extended temp files',
            ],
            'cold' => [
                'days' => env('STORAGE_TEMP_COLD_DAYS', 30), // 30 days
                'description' => 'Old temp files before deletion',
            ],
        ],
        'audit_trail' => [
            'hot' => [
                'days' => env('STORAGE_AUDIT_HOT_DAYS', 365), // 1 year
                'description' => 'Recent audit logs',
            ],
            'warm' => [
                'days' => env('STORAGE_AUDIT_WARM_DAYS', 1825), // 5 years
                'description' => 'Historical audit data',
            ],
            'cold' => [
                'days' => env('STORAGE_AUDIT_COLD_DAYS', 5475), // 15 years (legal requirement)
                'description' => 'Long-term audit archival',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Archive Schedule
    |--------------------------------------------------------------------------
    |
    | Schedule for automatic archiving of data between storage tiers.
    |
    */
    'auto_archive_schedule' => [
        'enabled' => env('STORAGE_AUTO_ARCHIVE_ENABLED', true),
        'frequency' => env('STORAGE_ARCHIVE_FREQUENCY', 'daily'), // daily, weekly, monthly
        'time' => env('STORAGE_ARCHIVE_TIME', '02:00'), // 2 AM
        'batch_size' => env('STORAGE_ARCHIVE_BATCH_SIZE', 1000), // Files per batch
        'compression' => [
            'enabled' => true,
            'algorithm' => 'gzip', // gzip, bzip2, xz
            'level' => 6, // 1-9, higher = better compression but slower
        ],
        'encryption' => [
            'enabled' => env('STORAGE_ARCHIVE_ENCRYPTION_ENABLED', true),
            'algorithm' => 'aes-256-gcm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delete After Days
    |--------------------------------------------------------------------------
    |
    | Automatic deletion rules for temporary and expired data.
    |
    */
    'delete_after_days' => [
        'temp_files' => env('STORAGE_DELETE_TEMP_DAYS', 7),
        'cache_files' => env('STORAGE_DELETE_CACHE_DAYS', 30),
        'session_files' => env('STORAGE_DELETE_SESSION_DAYS', 30),
        'failed_uploads' => env('STORAGE_DELETE_FAILED_UPLOADS_DAYS', 1),
        'expired_tokens' => env('STORAGE_DELETE_EXPIRED_TOKENS_DAYS', 30),
        'old_backups' => env('STORAGE_DELETE_OLD_BACKUPS_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal Hold Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for legal hold tags that prevent automatic deletion.
    |
    */
    'legal_hold_tag' => [
        'enabled' => true,
        'tag_name' => 'legal_hold',
        'auto_apply' => [
            'enabled' => false,
            'triggers' => ['lawsuit_filed', 'regulatory_inquiry', 'internal_investigation'],
        ],
        'override_permissions' => ['executives', 'legal_team'],
        'notification' => [
            'enabled' => true,
            'recipients' => ['legal@sibali.id', 'compliance@sibali.id'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Purge Policy
    |--------------------------------------------------------------------------
    |
    | Configuration for data purging with safety measures.
    |
    */
    'purge_policy' => [
        'dry_run_windows' => [
            'enabled' => true,
            'days_before_actual_purge' => env('STORAGE_PURGE_DRY_RUN_DAYS', 7),
            'notification_enabled' => true,
        ],
        'batch_size' => env('STORAGE_PURGE_BATCH_SIZE', 500),
        'max_runtime_seconds' => env('STORAGE_PURGE_MAX_RUNTIME', 3600), // 1 hour
        'error_handling' => [
            'continue_on_error' => true,
            'max_errors_before_stop' => 10,
            'log_all_errors' => true,
        ],
        'backup_before_purge' => [
            'enabled' => env('STORAGE_BACKUP_BEFORE_PURGE', true),
            'retention_days' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Notifications for storage lifecycle events.
    |
    */
    'notification_before_delete' => [
        'enabled' => env('STORAGE_NOTIFICATION_ENABLED', true),
        'days_notice' => env('STORAGE_NOTIFICATION_DAYS', 30),
        'recipients' => [
            'storage_admin' => ['it@sibali.id'],
            'content_owners' => true, // Notify owners of content being deleted
            'department_heads' => ['executives', 'header'],
        ],
        'channels' => ['email', 'dashboard_alert'],
        'exclusions' => [
            'temp_files' => true, // Don't notify for temp file deletions
            'cache_files' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Quotas
    |--------------------------------------------------------------------------
    |
    | Storage quotas per department/user to prevent runaway usage.
    |
    */
    'storage_quotas' => [
        'per_department' => [
            'operations' => env('STORAGE_QUOTA_OPERATIONS_GB', 1000), // 1TB
            'finance' => env('STORAGE_QUOTA_FINANCE_GB', 500),       // 500GB
            'it' => env('STORAGE_QUOTA_IT_GB', 2000),                // 2TB
            'engagement' => env('STORAGE_QUOTA_ENGAGEMENT_GB', 300), // 300GB
            'academic' => env('STORAGE_QUOTA_ACADEMIC_GB', 1500),    // 1.5TB
            'sales' => env('STORAGE_QUOTA_SALES_GB', 200),           // 200GB
            'hr' => env('STORAGE_QUOTA_HR_GB', 100),                 // 100GB
            'pr' => env('STORAGE_QUOTA_PR_GB', 500),                 // 500GB
            'rd' => env('STORAGE_QUOTA_RD_GB', 800),                 // 800GB
        ],
        'per_user' => env('STORAGE_QUOTA_PER_USER_GB', 10), // 10GB per user
        'warning_threshold_percentage' => env('STORAGE_WARNING_THRESHOLD', 80), // Warn at 80% usage
        'enforcement' => [
            'block_uploads' => true,
            'notify_admin' => true,
            'auto_cleanup' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance and Auditing
    |--------------------------------------------------------------------------
    |
    | Compliance settings for data retention and audit requirements.
    |
    */
    'compliance' => [
        'gdpr_compliance' => [
            'enabled' => env('STORAGE_GDPR_ENABLED', true),
            'data_deletion_grace_period' => 30, // Days to comply with deletion requests
            'anonymization_required' => true,
        ],
        'audit_logging' => [
            'enabled' => true,
            'log_retention_actions' => true,
            'log_access_patterns' => true,
            'log_deletion_events' => true,
        ],
        'data_classification' => [
            'enabled' => true,
            'levels' => ['public', 'internal', 'confidential', 'restricted'],
            'auto_classification' => [
                'enabled' => false,
                'rules' => [
                    'financial_data' => 'confidential',
                    'personal_data' => 'restricted',
                    'logs' => 'internal',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Optimization
    |--------------------------------------------------------------------------
    |
    | Automatic optimization features for storage efficiency.
    |
    */
    'optimization' => [
        'deduplication' => [
            'enabled' => env('STORAGE_DEDUPE_ENABLED', true),
            'algorithm' => 'content_hash', // content_hash, filename, metadata
            'min_file_size_kb' => 100, // Only dedupe files larger than this
        ],
        'compression' => [
            'enabled' => env('STORAGE_COMPRESSION_ENABLED', true),
            'types' => ['gzip', 'brotli'],
            'file_types' => ['text', 'json', 'xml', 'css', 'js', 'html'],
            'min_size_kb' => 10,
        ],
        'tiering' => [
            'enabled' => env('STORAGE_TIERING_ENABLED', true),
            'auto_tier' => true,
            'access_pattern_analysis' => [
                'enabled' => true,
                'analysis_window_days' => 30,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Disaster Recovery
    |--------------------------------------------------------------------------
    |
    | Configuration for disaster recovery and data restoration.
    |
    */
    'disaster_recovery' => [
        'cross_region_replication' => [
            'enabled' => env('STORAGE_CROSS_REGION_ENABLED', false),
            'regions' => ['asia-southeast1', 'asia-southeast2'],
            'replication_type' => 'async', // sync, async
        ],
        'backup_validation' => [
            'enabled' => true,
            'frequency' => 'weekly',
            'test_restoration' => true,
        ],
        'recovery_time_objective' => env('STORAGE_RTO_HOURS', 4), // Hours
        'recovery_point_objective' => env('STORAGE_RPO_HOURS', 1), // Hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Reporting
    |--------------------------------------------------------------------------
    |
    | Monitoring and reporting configuration for storage usage.
    |
    */
    'monitoring' => [
        'usage_tracking' => [
            'enabled' => true,
            'frequency' => 'daily',
            'alerts' => [
                'quota_exceeded' => true,
                'unusual_growth' => true,
                'storage_full' => true,
            ],
        ],
        'performance_metrics' => [
            'enabled' => true,
            'metrics' => [
                'storage_utilization',
                'access_patterns',
                'retention_compliance',
                'cost_analysis',
            ],
        ],
        'reporting' => [
            'enabled' => true,
            'frequency' => 'monthly',
            'recipients' => ['it@sibali.id', 'executives'],
            'formats' => ['pdf', 'excel'],
        ],
    ],
];
