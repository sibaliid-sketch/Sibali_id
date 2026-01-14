<?php

return [
    'templates' => [
        'welcome' => [
            'subject' => 'Welcome to {company_name}',
            'body' => 'Dear {user_name}, welcome to our platform. Your account has been created successfully.',
        ],
        'password_reset' => [
            'subject' => 'Password Reset Request',
            'body' => 'Click the link to reset your password: {reset_link}',
        ],
        'notification' => [
            'subject' => 'New Notification',
            'body' => 'You have a new notification: {message}',
        ],
        'invoice' => [
            'subject' => 'Your Invoice from {company_name}',
            'body' => 'Dear {user_name}, please find your invoice attached.',
        ],
        'marketing' => [
            'subject' => 'Exclusive Offer for You',
            'body' => 'Hi {user_name}, check out our latest offers.',
        ],
    ],
    'placeholders' => [
        'user_name' => 'User\'s name',
        'company_name' => 'Company name',
        'reset_link' => 'Password reset link',
        'message' => 'Notification message',
    ],
    'smtp_settings' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'noreply@example.com',
        'password' => env('MAIL_PASSWORD'),
    ],
];
