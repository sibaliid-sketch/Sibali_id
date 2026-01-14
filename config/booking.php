<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking & Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the booking and scheduling system.
    | It handles class bookings, availability management, and conflict detection
    | rules for the LMS platform.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Maximum Bookings per User
    |--------------------------------------------------------------------------
    |
    | Maximum number of active bookings allowed per user.
    | Set to 0 for unlimited.
    |
    */
    'max_bookings_per_user' => env('BOOKING_MAX_PER_USER', 5),

    /*
    |--------------------------------------------------------------------------
    | Minimum Notice Hours
    |--------------------------------------------------------------------------
    |
    | Minimum hours notice required before booking a class.
    |
    */
    'min_notice_hours' => env('BOOKING_MIN_NOTICE_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy
    |--------------------------------------------------------------------------
    |
    | Cancellation policy with penalty windows and fees.
    |
    */
    'cancellation_policy' => [
        'free_cancellation_hours' => 24, // Free cancellation up to 24 hours before
        'penalty_windows' => [
            [
                'hours_before' => 24,
                'penalty_percentage' => 50, // 50% penalty
            ],
            [
                'hours_before' => 12,
                'penalty_percentage' => 75, // 75% penalty
            ],
            [
                'hours_before' => 6,
                'penalty_percentage' => 100, // 100% penalty (no refund)
            ],
        ],
        'no_show_penalty_percentage' => 100, // 100% penalty for no-shows
    ],

    /*
    |--------------------------------------------------------------------------
    | Slot Granularity
    |--------------------------------------------------------------------------
    |
    | Time slot granularity in minutes for booking system.
    | Common values: 15, 30, 60 minutes.
    |
    */
    'slot_granularity_minutes' => env('BOOKING_SLOT_GRANULARITY', 60),

    /*
    |--------------------------------------------------------------------------
    | Conflict Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting scheduling conflicts.
    |
    */
    'conflict_detection' => [
        'algorithm' => env('BOOKING_CONFLICT_ALGORITHM', 'strict'), // strict or soft
        'check_double_booking' => true,
        'check_teacher_availability' => true,
        'check_room_capacity' => true,
        'allow_overlapping' => false,
        'buffer_time_minutes' => env('BOOKING_BUFFER_MINUTES', 15), // Buffer between sessions
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Reminder Hours
    |--------------------------------------------------------------------------
    |
    | Hours before class start to send automatic reminders.
    |
    */
    'auto_reminder_hours' => env('BOOKING_REMINDER_HOURS', [24, 2, 0.5]), // Array of hours

    /*
    |--------------------------------------------------------------------------
    | Timezone Handling
    |--------------------------------------------------------------------------
    |
    | How the system handles timezones for bookings.
    |
    */
    'timezones_handling' => [
        'store_in_utc' => true,
        'display_local' => true,
        'default_timezone' => env('BOOKING_DEFAULT_TIMEZONE', 'Asia/Jakarta'),
        'allowed_timezones' => [
            'Asia/Jakarta',
            'Asia/Makassar',
            'Asia/Jayapura',
            'UTC',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Types
    |--------------------------------------------------------------------------
    |
    | Different types of bookings supported by the system.
    |
    */
    'booking_types' => [
        'private' => [
            'max_participants' => 1,
            'requires_payment' => true,
            'auto_confirm' => false,
        ],
        'regular' => [
            'max_participants' => env('BOOKING_REGULAR_MAX_PARTICIPANTS', 10),
            'requires_payment' => true,
            'auto_confirm' => true,
        ],
        'rumah_belajar' => [
            'max_participants' => env('BOOKING_RUMAH_BELAJAR_MAX_PARTICIPANTS', 5),
            'requires_payment' => true,
            'auto_confirm' => true,
        ],
        'special_program' => [
            'max_participants' => env('BOOKING_SPECIAL_MAX_PARTICIPANTS', 20),
            'requires_payment' => true,
            'auto_confirm' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Availability Rules
    |--------------------------------------------------------------------------
    |
    | Rules for determining availability of teachers and resources.
    |
    */
    'availability_rules' => [
        'max_daily_hours' => env('BOOKING_MAX_DAILY_HOURS', 8),
        'max_weekly_hours' => env('BOOKING_MAX_WEEKLY_HOURS', 40),
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
        'working_hours_start' => '06:00',
        'working_hours_end' => '22:00',
        'break_duration_minutes' => 15,
        'consecutive_sessions_max' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Waitlist Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for booking waitlists when classes are full.
    |
    */
    'waitlist' => [
        'enabled' => true,
        'max_waitlist_size' => env('BOOKING_WAITLIST_MAX_SIZE', 50),
        'auto_promote' => true,
        'promotion_hours_before' => 48, // Hours before class to promote from waitlist
        'notification_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recurring Bookings
    |--------------------------------------------------------------------------
    |
    | Configuration for recurring class bookings.
    |
    */
    'recurring_bookings' => [
        'enabled' => true,
        'max_recurrence_months' => env('BOOKING_MAX_RECURRENCE_MONTHS', 6),
        'allowed_frequencies' => ['weekly', 'biweekly', 'monthly'],
        'auto_renewal' => false,
        'renewal_notice_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Management
    |--------------------------------------------------------------------------
    |
    | Configuration for managing physical/virtual resources for bookings.
    |
    */
    'resource_management' => [
        'virtual_rooms' => [
            'enabled' => true,
            'auto_assign' => true,
            'meeting_providers' => ['zoom', 'google_meet', 'microsoft_teams'],
        ],
        'physical_rooms' => [
            'enabled' => false, // Not used in current setup
            'capacity_tracking' => true,
        ],
        'equipment_booking' => [
            'enabled' => false,
            'equipment_types' => [], // To be defined if needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Workflow
    |--------------------------------------------------------------------------
    |
    | Workflow configuration for booking approval and processing.
    |
    */
    'workflow' => [
        'auto_approval' => [
            'enabled' => true,
            'max_sessions_per_week' => 5,
            'vip_users' => true, // Auto-approve for VIP users
        ],
        'manual_approval_roles' => ['teacher', 'coordinator', 'manager'],
        'approval_timeout_hours' => 24, // Auto-reject if not approved within hours
        'modification_rules' => [
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'modification_deadline_hours' => 12,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Notification settings for booking-related events.
    |
    */
    'notifications' => [
        'booking_confirmation' => true,
        'booking_reminder' => true,
        'booking_cancellation' => true,
        'waitlist_notification' => true,
        'teacher_assignment' => true,
        'payment_reminder' => true,
        'channels' => ['email', 'sms', 'in_app'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Integration settings with external calendar and scheduling systems.
    |
    */
    'integrations' => [
        'google_calendar' => [
            'enabled' => env('BOOKING_GOOGLE_CALENDAR_ENABLED', false),
            'sync_directions' => ['export', 'import'], // export bookings, import external events
        ],
        'outlook_calendar' => [
            'enabled' => env('BOOKING_OUTLOOK_CALENDAR_ENABLED', false),
            'sync_directions' => ['export'],
        ],
        'zoom' => [
            'enabled' => env('BOOKING_ZOOM_ENABLED', true),
            'auto_create_meetings' => true,
            'meeting_settings' => [
                'join_before_host' => false,
                'mute_participants' => true,
                'auto_record' => false,
            ],
        ],
    ],
];
