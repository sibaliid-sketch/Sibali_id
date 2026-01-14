<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for attendance tracking and management.
    | It handles real-time attendance capture, sync rules, and penalty systems
    | for field sales and office staff.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Check-in Window
    |--------------------------------------------------------------------------
    |
    | Time window (in minutes) before and after the scheduled start time
    | when check-in is allowed.
    |
    */
    'checkin_window' => [
        'before_minutes' => env('ATTENDANCE_CHECKIN_BEFORE_MINUTES', 15),
        'after_minutes' => env('ATTENDANCE_CHECKIN_AFTER_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Mark Absent
    |--------------------------------------------------------------------------
    |
    | Automatically mark employee as absent if they don't check-in
    | within the specified time after scheduled start time.
    |
    */
    'auto_mark_absent_after_minutes' => env('ATTENDANCE_AUTO_ABSENT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Late Threshold
    |--------------------------------------------------------------------------
    |
    | Time threshold (in minutes) after which an employee is considered late.
    |
    */
    'late_threshold_minutes' => env('ATTENDANCE_LATE_THRESHOLD_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Substitution Rules
    |--------------------------------------------------------------------------
    |
    | Rules for handling attendance substitutions and adjustments.
    |
    */
    'substitution_rules' => [
        'allow_substitution' => true,
        'max_substitutions_per_month' => 3,
        'require_approval' => true,
        'approval_roles' => ['supervisor', 'manager', 'header'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Interval
    |--------------------------------------------------------------------------
    |
    | Interval (in minutes) for syncing attendance data from field sales
    | devices and external systems.
    |
    */
    'sync_interval' => env('ATTENDANCE_SYNC_INTERVAL_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Attendance Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain attendance records before archiving.
    |
    */
    'attendance_retention_days' => env('ATTENDANCE_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Location Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for GPS location tracking during check-in/check-out.
    |
    */
    'location_tracking' => [
        'enabled' => env('ATTENDANCE_LOCATION_TRACKING_ENABLED', true),
        'required_accuracy_meters' => env('ATTENDANCE_LOCATION_ACCURACY_METERS', 100),
        'allowed_office_radius_meters' => env('ATTENDANCE_OFFICE_RADIUS_METERS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Overtime Rules
    |--------------------------------------------------------------------------
    |
    | Rules for calculating and approving overtime hours.
    |
    */
    'overtime_rules' => [
        'enabled' => true,
        'max_daily_hours' => 3,
        'max_weekly_hours' => 16,
        'require_approval' => true,
        'approval_roles' => ['supervisor', 'manager', 'header'],
        'auto_approval_threshold_hours' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Holiday and Leave Integration
    |--------------------------------------------------------------------------
    |
    | Integration settings with holiday calendar and leave management.
    |
    */
    'holiday_leave_integration' => [
        'auto_exclude_holidays' => true,
        'auto_exclude_approved_leave' => true,
        'mark_weekends_off' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for attendance-related notifications to employees and managers.
    |
    */
    'notifications' => [
        'late_checkin_alert' => true,
        'absent_alert' => true,
        'overtime_alert' => true,
        'weekly_summary' => true,
        'monthly_report' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Biometric Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for biometric device integration (fingerprint, facial recognition).
    |
    */
    'biometric_integration' => [
        'enabled' => env('ATTENDANCE_BIOMETRIC_ENABLED', false),
        'device_types' => ['fingerprint', 'facial_recognition'],
        'sync_interval_minutes' => 1,
        'fallback_methods' => ['pin', 'rfid'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penalty System
    |--------------------------------------------------------------------------
    |
    | Automatic penalty calculation for attendance violations.
    |
    */
    'penalty_system' => [
        'enabled' => true,
        'late_penalty_per_minute' => 0.5, // deduction amount
        'absent_penalty_per_day' => 1,    // deduction amount
        'max_monthly_penalty' => 5,       // maximum deduction per month
        'grace_period_minutes' => 5,      // grace period before penalties apply
    ],
];
