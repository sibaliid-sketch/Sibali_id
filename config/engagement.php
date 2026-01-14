<?php

return [
    'tracking' => [
        'events' => [
            'page_views' => true,
            'time_on_page' => true,
            'clicks' => true,
            'scroll_depth' => true,
            'form_submissions' => true,
        ],
        'user_journey' => [
            'funnel_stages' => ['awareness', 'interest', 'consideration', 'purchase', 'retention'],
            'conversion_points' => ['signup', 'first_purchase', 'repeat_purchase'],
        ],
        'session_tracking' => [
            'duration_threshold' => 30, // minutes
            'bounce_rate_alert' => 70, // percentage
        ],
    ],
    'retention_thresholds' => [
        'inactive_days' => 30,
        'churn_risk_score' => 0.7,
        'reengagement_campaigns' => [
            'email_reminders' => true,
            'personalized_offers' => true,
            'loyalty_rewards' => true,
        ],
    ],
    'personalization' => [
        'dynamic_content' => true,
        'recommendation_engine' => [
            'algorithm' => 'collaborative_filtering',
            'data_sources' => ['purchase_history', 'browsing_behavior'],
        ],
        'segmentation' => [
            'demographic' => true,
            'behavioral' => true,
            'psychographic' => false,
        ],
    ],
    'feedback_loops' => [
        'surveys' => [
            'post_purchase' => true,
            'periodic' => 'quarterly',
        ],
        'nps_tracking' => true,
        'sentiment_analysis' => [
            'enabled' => false,
            'provider' => 'google_natural_language',
        ],
    ],
];
