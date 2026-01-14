<?php

return [
    'integration_parameters' => [
        'provider' => 'midtrans', // or other QRIS providers
        'api_key' => env('QRIS_API_KEY'),
        'api_secret' => env('QRIS_API_SECRET'),
        'sandbox_mode' => env('APP_ENV') === 'local',
        'webhook_url' => env('APP_URL') . '/webhooks/qris',
    ],
    'payment_methods' => [
        'qris' => [
            'enabled' => true,
            'name' => 'QRIS Payment',
            'description' => 'Pay using QRIS compatible apps',
            'fee_percentage' => 0.5, // 0.5%
            'min_amount' => 1000,
            'max_amount' => 10000000,
        ],
    ],
    'transaction_settings' => [
        'timeout' => 900, // seconds
        'retry_attempts' => 3,
        'auto_cancel' => true,
        'refund_policy' => 'within_24_hours',
    ],
    'validation_rules' => [
        'qr_code_format' => 'base64',
        'amount_precision' => 2,
        'currency' => 'IDR',
        'required_fields' => ['amount', 'reference_id', 'merchant_id'],
    ],
    'logging' => [
        'transaction_logs' => true,
        'error_logs' => true,
        'audit_trail' => true,
    ],
];
