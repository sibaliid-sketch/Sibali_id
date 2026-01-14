<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internal Tools Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for internal tools used by ops/HR/IT
    | departments. It manages access controls, maintenance windows, and
    | operational settings for various internal applications.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enabled Tools
    |--------------------------------------------------------------------------
    |
    | List of internal tools that are currently enabled and available for use.
    | Each tool should correspond to a module in App\Internal namespace.
    |
    */
    'tools_enabled' => [
        'employee_portal',
        'payroll_export',
        'internal_reports',
        'hr_dashboard',
        'it_inventory',
        'ops_monitoring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Defines which roles are allowed to access each internal tool.
    | Roles should match those defined in config/roles.php
    |
    */
    'access_control' => [
        'employee_portal' => ['basic_staff', 'senior_staff', 'leader', 'supervisor', 'manager', 'header', 'executives'],
        'payroll_export' => ['supervisor', 'manager', 'header', 'executives'],
        'internal_reports' => ['leader', 'supervisor', 'manager', 'header', 'executives'],
        'hr_dashboard' => ['supervisor', 'manager', 'header', 'executives'],
        'it_inventory' => ['leader', 'supervisor', 'manager', 'header', 'executives'],
        'ops_monitoring' => ['supervisor', 'manager', 'header', 'executives'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Window
    |--------------------------------------------------------------------------
    |
    | Scheduled maintenance window for internal tools.
    | Format: 'day_of_week hour:minute-hour:minute'
    | Example: 'sunday 02:00-04:00'
    |
    */
    'maintenance_window' => 'sunday 02:00-04:00',

    /*
    |--------------------------------------------------------------------------
    | Logging Level
    |--------------------------------------------------------------------------
    |
    | Logging level for internal tools operations.
    | Options: emergency, alert, critical, error, warning, notice, info, debug
    |
    */
    'logging_level' => env('INTERNAL_TOOLS_LOG_LEVEL', 'info'),

    /*
    |--------------------------------------------------------------------------
    | Backup Quota per Tool
    |--------------------------------------------------------------------------
    |
    | Maximum backup storage quota allowed per tool (in MB).
    | Set to 0 for unlimited.
    |
    */
    'backup_quota_per_tool' => [
        'employee_portal' => 1000, // 1GB
        'payroll_export' => 5000,  // 5GB
        'internal_reports' => 2000, // 2GB
        'hr_dashboard' => 1000,    // 1GB
        'it_inventory' => 500,     // 500MB
        'ops_monitoring' => 2000,  // 2GB
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Allowlist
    |--------------------------------------------------------------------------
    |
    | List of allowed IP addresses or CIDR blocks for accessing internal tools.
    | Leave empty to allow all IPs (not recommended for production).
    |
    */
    'ip_allowlist' => [
        // '192.168.1.0/24',
        // '10.0.0.0/8',
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Whether 2FA is enforced for internal tools access.
    |
    */
    'enforce_2fa' => env('INTERNAL_TOOLS_ENFORCE_2FA', true),

    /*
    |--------------------------------------------------------------------------
    | Session Timeout
    |--------------------------------------------------------------------------
    |
    | Session timeout for internal tools in minutes.
    |
    */
    'session_timeout_minutes' => env('INTERNAL_TOOLS_SESSION_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for internal tools API endpoints.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'attempts' => 100, // requests per minute
        'decay_minutes' => 1,
    ],
];
