<?php

return [
    'connectors' => [
        'facebook' => [
            'app_id' => env('FB_APP_ID'),
            'app_secret' => env('FB_APP_SECRET'),
            'api_version' => 'v12.0',
            'permissions' => ['email', 'public_profile', 'pages_read_engagement'],
        ],
        'twitter' => [
            'consumer_key' => env('TWITTER_CONSUMER_KEY'),
            'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
            'access_token' => env('TWITTER_ACCESS_TOKEN'),
            'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
        ],
        'instagram' => [
            'app_id' => env('IG_APP_ID'),
            'app_secret' => env('IG_APP_SECRET'),
            'redirect_uri' => env('IG_REDIRECT_URI'),
        ],
        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        ],
    ],
    'policies' => [
        'auto_posting' => [
            'enabled' => false,
            'platforms' => ['facebook', 'twitter'],
            'scheduling' => 'daily',
            'content_types' => ['text', 'image', 'link'],
        ],
        'engagement_tracking' => [
            'likes' => true,
            'shares' => true,
            'comments' => true,
            'follows' => true,
        ],
        'content_moderation' => [
            'auto_approve' => false,
            'keywords_filter' => ['spam', 'offensive'],
            'sentiment_analysis' => false,
        ],
        'privacy_settings' => [
            'public_posts' => false,
            'data_sharing' => 'minimal',
        ],
    ],
    'analytics' => [
        'metrics' => ['reach', 'engagement', 'conversions'],
        'reporting' => 'weekly',
        'integrations' => ['google_analytics', 'facebook_insights'],
    ],
];
