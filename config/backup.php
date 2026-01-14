<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Backup policy & targets for automated, encrypted backups for DB & assets.
    |
    */

    'enabled' => env('BACKUP_ENABLED', true),

    'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // cron expression

    'targets' => [
        'db' => true,
        'uploads' => true,
        's3' => false,
        'configs' => true,
    ],

    'retention' => [
        'hot' => 7, // days
        'warm' => 30, // days
        'cold' => 365, // days
    ],

    'encryption' => [
        'enabled' => true,
        'method' => 'AES-256-GCM',
    ],

    'destination' => [
        's3' => [
            'bucket' => env('BACKUP_S3_BUCKET'),
            'endpoint' => env('BACKUP_S3_ENDPOINT'),
        ],
    ],

    'verify_after_backup' => [
        'enabled' => true,
        'method' => 'checksum',
    ],

    'notifications_on_failure' => [
        'enabled' => true,
        'channels' => ['email'],
    ],

    'parallelism' => env('BACKUP_PARALLELISM', 2),

    // Encrypted key rotation
    'key_rotation' => [
        'enabled' => true,
        'interval_days' => 90,
    ],

    // Restore test cadence
    'restore_test' => [
        'enabled' => true,
        'cadence_days' => 30,
    ],
];
