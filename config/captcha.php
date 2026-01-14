<?php

return [
    'enabled' => true,
    'provider' => 'recaptcha', // recaptcha, hcaptcha, or custom
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'version' => 'v2', // v2 or v3
        'threshold' => 0.5, // for v3
    ],
    'hcaptcha' => [
        'site_key' => env('HCAPTCHA_SITE_KEY'),
        'secret_key' => env('HCAPTCHA_SECRET_KEY'),
    ],
    'custom' => [
        'endpoint' => 'https://custom-captcha.example.com/verify',
        'api_key' => env('CUSTOM_CAPTCHA_API_KEY'),
    ],
    'forms' => [
        'login' => true,
        'registration' => true,
        'contact' => true,
        'password_reset' => false,
    ],
    'bot_protection' => [
        'honeypot_fields' => ['website', 'phone'],
        'time_based' => [
            'min_time' => 3, // seconds
            'max_time' => 3600,
        ],
        'behavioral_analysis' => false,
    ],
    'logging' => [
        'failed_attempts' => true,
        'suspicious_activity' => true,
    ],
];
