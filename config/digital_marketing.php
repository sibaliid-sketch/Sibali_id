<?php

return [
    'automation' => [
        'email_campaigns' => [
            'welcome_series' => ['email1', 'email2', 'email3'],
            'abandoned_cart' => ['reminder1', 'reminder2'],
            'birthday' => ['birthday_email'],
        ],
        'social_media' => [
            'auto_post' => true,
            'scheduling' => 'weekly',
            'platforms' => ['facebook', 'twitter', 'instagram'],
        ],
        'sms_campaigns' => [
            'promotional' => true,
            'transactional' => true,
        ],
    ],
    'content_rules' => [
        'seo_optimization' => [
            'meta_tags' => true,
            'sitemap' => true,
            'robots_txt' => true,
        ],
        'content_types' => ['blog', 'video', 'infographic', 'ebook'],
        'publishing_workflow' => ['draft', 'review', 'publish'],
    ],
    'analytics' => [
        'google_analytics' => [
            'tracking_id' => env('GA_TRACKING_ID'),
            'anonymize_ip' => true,
        ],
        'facebook_pixel' => env('FB_PIXEL_ID'),
        'conversion_tracking' => true,
    ],
    'ad_campaigns' => [
        'platforms' => ['google_ads', 'facebook_ads', 'linkedin_ads'],
        'budget_limits' => [
            'daily' => 100,
            'monthly' => 3000,
        ],
        'targeting' => [
            'demographics' => ['age', 'location', 'interests'],
            'behavioral' => ['past_purchases', 'engagement'],
        ],
    ],
    'lead_generation' => [
        'forms' => ['contact', 'newsletter', 'demo_request'],
        'landing_pages' => ['product', 'service', 'pricing'],
        'cta_buttons' => ['primary', 'secondary', 'tertiary'],
    ],
];
