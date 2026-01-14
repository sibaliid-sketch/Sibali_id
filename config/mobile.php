<?php

return [
    'pwa_settings' => [
        'enabled' => true,
        'manifest' => [
            'name' => 'Sibali.id PWA',
            'short_name' => 'Sibali',
            'start_url' => '/',
            'display' => 'standalone',
            'theme_color' => '#8B0000',
            'background_color' => '#87CEEB',
            'icons' => [
                ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ],
        'service_worker' => [
            'cache_strategy' => 'network_first',
            'offline_page' => '/offline.html',
            'precache_resources' => ['/', '/css/app.css', '/js/app.js'],
        ],
    ],
    'responsive_design' => [
        'breakpoints' => [
            'mobile' => 320,
            'tablet' => 768,
            'desktop' => 1024,
        ],
        'fluid_typography' => true,
        'touch_friendly' => true,
    ],
    'app_features' => [
        'push_notifications' => [
            'enabled' => true,
            'vapid_keys' => [
                'public' => env('VAPID_PUBLIC_KEY'),
                'private' => env('VAPID_PRIVATE_KEY'),
            ],
            'topics' => ['updates', 'reminders', 'promotions'],
        ],
        'offline_mode' => [
            'enabled' => true,
            'sync_on_reconnect' => true,
            'data_limits' => 'unlimited',
        ],
        'biometric_auth' => [
            'enabled' => false,
            'methods' => ['fingerprint', 'face_id'],
        ],
    ],
    'performance' => [
        'lazy_loading' => true,
        'image_optimization' => true,
        'minification' => true,
        'caching' => [
            'static_assets' => 86400, // 1 day
            'api_responses' => 3600, // 1 hour
        ],
    ],
];
