<?php

return [
    'default_disk' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],
    'lifecycle_rules' => [
        'temp_files' => [
            'prefix' => 'temp/',
            'expiration_days' => 7,
        ],
        'logs' => [
            'prefix' => 'logs/',
            'expiration_days' => 30,
        ],
        'backups' => [
            'prefix' => 'backups/',
            'expiration_days' => 365,
        ],
    ],
    'compression' => [
        'enabled' => true,
        'algorithm' => 'gzip',
        'level' => 6,
    ],
    'cdn_integration' => [
        'enabled' => false,
        'provider' => 'cloudflare',
        'api_key' => env('CDN_API_KEY'),
    ],
];
