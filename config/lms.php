<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LMS Configuration
    |--------------------------------------------------------------------------
    |
    | LMS business rules & thresholds for academic logic: class sizes, grading schema, certificate rules.
    |
    */

    'max_class_size' => env('LMS_MAX_CLASS_SIZE', 30),

    'grading_scale' => [
        'A' => [90, 100],
        'B' => [80, 89],
        'C' => [70, 79],
        'D' => [60, 69],
        'F' => [0, 59],
    ],

    'passing_threshold' => env('LMS_PASSING_THRESHOLD', 60),

    'attendance_required_pct' => env('LMS_ATTENDANCE_REQUIRED', 75),

    'certificate_template_id' => env('LMS_CERTIFICATE_TEMPLATE', 'default'),

    'late_submission_policy' => [
        'penalty_per_day' => 5, // percentage
    ],

    'auto_grade_rules' => [
        'enabled' => true,
        'for_mcq' => true,
    ],

    'language_lab_config' => [
        'recording_retention_days' => 90,
    ],

    'practice_limits' => [
        'max_attempts' => 3,
        'time_limit_minutes' => 60,
    ],

    // Feature flags
    'adaptive_learning' => [
        'enabled' => false,
    ],

    'gamification_hooks' => [
        'enabled' => true,
        'badges' => true,
        'leaderboards' => true,
    ],
];
