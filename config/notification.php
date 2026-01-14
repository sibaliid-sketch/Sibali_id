<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Notification rules & routing for smart alerts (real-time + predictive).
    |
    */

    'channels' => [
        'in_app' => true,
        'email' => true,
        'sms' => false,
        'push' => false,
        'whatsapp' => false,
    ],

    'default_channel_priority' => ['email', 'in_app', 'sms'],

    'channel_config' => [
        'email' => [
            'driver' => 'smtp',
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'timeout' => 30,
        ],
        'sms' => [
            'provider' => 'twilio',
            'api_key' => env('SMS_API_KEY'),
        ],
    ],

    'throttling' => [
        'per_user' => 10, // notifications per hour
    ],

    'predictive_rules' => [
        'engagement_scoring' => [
            'thresholds' => [
                'high' => 0.8,
                'medium' => 0.5,
                'low' => 0.2,
            ],
            'triggers' => [
                'high' => ['email', 'push'],
                'medium' => ['email'],
                'low' => ['in_app'],
            ],
        ],
    ],

    'snooze' => [
        'enabled' => true,
        'default_duration' => 3600, // seconds
    ],

    'mute_options' => [
        'enabled' => true,
        'categories' => ['marketing', 'system'],
    ],

    'retry_policy' => [
        'max_attempts' => 3,
        'backoff' => 'exponential',
    ],

    'audit_channel' => 'notifications_audit',
];
