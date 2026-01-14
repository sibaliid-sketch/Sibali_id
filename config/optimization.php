<?php

return [
    'caching' => [
        'enabled' => true,
        'driver' => 'redis', // file, redis, memcached
        'ttl' => 3600, // seconds
        'prefix' => 'sibali_',
    ],
    'compression' => [
        'gzip' => true,
        'brotli' => false,
        'level' => 6,
    ],
    'minification' => [
        'css' => true,
        'js' => true,
        'html' => false,
    ],
    'image_optimization' => [
        'enabled' => true,
        'formats' => ['webp', 'avif'],
        'quality' => 85,
        'lazy_loading' => true,
    ],
    'database' => [
        'query_optimization' => true,
        'connection_pooling' => true,
        'read_replicas' => false,
    ],
    'cdn' => [
        'enabled' => false,
        'provider' => 'cloudflare',
        'zones' => ['assets', 'images'],
    ],
    'performance_flags' => [
        'opcache' => true,
        'jit' => false,
        'preload' => false,
    ],
];
