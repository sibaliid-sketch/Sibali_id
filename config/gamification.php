<?php

return [
    'rules' => [
        'points_system' => [
            'login' => 10,
            'course_completion' => 100,
            'quiz_pass' => 50,
            'referral' => 200,
            'forum_post' => 5,
        ],
        'badges' => [
            'first_login' => ['name' => 'Welcome', 'icon' => 'badge1.png'],
            'course_master' => ['name' => 'Course Master', 'icon' => 'badge2.png'],
            'quiz_champion' => ['name' => 'Quiz Champion', 'icon' => 'badge3.png'],
            'community_helper' => ['name' => 'Community Helper', 'icon' => 'badge4.png'],
        ],
        'levels' => [
            'thresholds' => [0, 100, 500, 1000, 2500, 5000],
            'names' => ['Beginner', 'Novice', 'Intermediate', 'Advanced', 'Expert', 'Master'],
        ],
        'leaderboards' => [
            'weekly' => true,
            'monthly' => true,
            'all_time' => true,
            'categories' => ['points', 'courses_completed', 'referrals'],
        ],
    ],
    'catalogs' => [
        'rewards' => [
            'discount_coupon' => ['value' => 10, 'cost' => 500],
            'free_course' => ['value' => 'any_course', 'cost' => 1000],
            'certificate_upgrade' => ['value' => 'premium', 'cost' => 750],
            'priority_support' => ['value' => '24h_response', 'cost' => 300],
        ],
        'achievements' => [
            'milestones' => [10, 25, 50, 100],
            'types' => ['courses', 'quizzes', 'logins', 'referrals'],
        ],
        'challenges' => [
            'daily' => ['complete_quiz', 'watch_video', 'post_forum'],
            'weekly' => ['refer_friend', 'complete_course', 'help_others'],
            'monthly' => ['top_leaderboard', 'perfect_score', 'community_contribution'],
        ],
    ],
    'engagement_boosters' => [
        'streaks' => [
            'login_streak' => ['bonus_multiplier' => 1.5, 'max_days' => 30],
            'learning_streak' => ['bonus_multiplier' => 2.0, 'max_days' => 7],
        ],
        'social_features' => [
            'friend_requests' => true,
            'group_challenges' => true,
            'public_profiles' => true,
        ],
        'notifications' => [
            'badge_earned' => true,
            'level_up' => true,
            'leaderboard_ranking' => true,
        ],
    ],
];
