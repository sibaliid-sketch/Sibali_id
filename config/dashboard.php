<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard & BI Layout Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for dashboard layouts and BI components.
    | It defines role-based dashboard widgets, KPI definitions, and reporting
    | settings for different user types.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Widgets by Role
    |--------------------------------------------------------------------------
    |
    | Dashboard widgets configuration for each user role.
    | Widgets are organized by priority and can be customized per role.
    |
    */
    'widgets_by_role' => [
        'executives' => [
            'primary' => [
                'revenue_overview',
                'student_enrollment_trends',
                'department_performance',
                'strategic_kpis',
            ],
            'secondary' => [
                'attendance_summary',
                'inquiry_conversion',
                'system_health',
                'recent_activities',
            ],
            'optional' => [
                'detailed_reports',
                'custom_analytics',
            ],
        ],
        'header' => [
            'primary' => [
                'department_kpis',
                'team_performance',
                'budget_overview',
                'project_status',
            ],
            'secondary' => [
                'attendance_department',
                'inquiry_department',
                'resource_utilization',
            ],
            'optional' => [
                'detailed_reports',
            ],
        ],
        'manager' => [
            'primary' => [
                'team_kpis',
                'attendance_team',
                'project_progress',
                'resource_allocation',
            ],
            'secondary' => [
                'inquiry_status',
                'performance_reviews',
                'training_completion',
            ],
            'optional' => [
                'detailed_reports',
            ],
        ],
        'supervisor' => [
            'primary' => [
                'team_overview',
                'attendance_supervision',
                'task_completion',
                'quality_metrics',
            ],
            'secondary' => [
                'inquiry_followup',
                'performance_indicators',
            ],
            'optional' => [
                'detailed_reports',
            ],
        ],
        'teacher' => [
            'primary' => [
                'class_schedule',
                'student_progress',
                'attendance_class',
                'assignment_grades',
            ],
            'secondary' => [
                'class_analytics',
                'student_feedback',
                'resource_usage',
            ],
            'optional' => [
                'detailed_reports',
            ],
        ],
        'student' => [
            'primary' => [
                'my_schedule',
                'my_progress',
                'my_grades',
                'upcoming_assignments',
            ],
            'secondary' => [
                'class_materials',
                'announcements',
                'peer_progress',
            ],
            'optional' => [
                'detailed_reports',
            ],
        ],
        'admin' => [
            'primary' => [
                'system_overview',
                'user_management',
                'security_alerts',
                'backup_status',
            ],
            'secondary' => [
                'performance_metrics',
                'error_logs',
                'audit_trail',
            ],
            'optional' => [
                'detailed_reports',
                'custom_analytics',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh Intervals
    |--------------------------------------------------------------------------
    |
    | Automatic refresh intervals for dashboard widgets (in seconds).
    | Different intervals for different types of data.
    |
    */
    'refresh_interval_seconds' => [
        'real_time' => env('DASHBOARD_REFRESH_REAL_TIME', 30),     // Live data
        'frequent' => env('DASHBOARD_REFRESH_FREQUENT', 300),     // 5 minutes
        'normal' => env('DASHBOARD_REFRESH_NORMAL', 1800),       // 30 minutes
        'infrequent' => env('DASHBOARD_REFRESH_INFREQUENT', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching settings for dashboard data to improve performance.
    |
    */
    'cache_ttl' => [
        'widget_data' => env('DASHBOARD_CACHE_WIDGET_TTL', 300),     // 5 minutes
        'kpi_data' => env('DASHBOARD_CACHE_KPI_TTL', 600),           // 10 minutes
        'report_data' => env('DASHBOARD_CACHE_REPORT_TTL', 1800),    // 30 minutes
        'static_data' => env('DASHBOARD_CACHE_STATIC_TTL', 3600),    // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Time Range
    |--------------------------------------------------------------------------
    |
    | Default time range for dashboard charts and reports.
    |
    */
    'default_time_range' => [
        'period' => env('DASHBOARD_DEFAULT_PERIOD', '30_days'),
        'available_ranges' => [
            '7_days',
            '30_days',
            '90_days',
            '6_months',
            '1_year',
            'custom',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Formats
    |--------------------------------------------------------------------------
    |
    | Supported export formats for dashboard data and reports.
    |
    */
    'export_formats' => [
        'pdf' => [
            'enabled' => true,
            'orientation' => 'landscape',
            'paper_size' => 'a4',
        ],
        'excel' => [
            'enabled' => true,
            'include_charts' => true,
            'multiple_sheets' => true,
        ],
        'csv' => [
            'enabled' => true,
            'delimiter' => ',',
            'include_headers' => true,
        ],
        'png' => [
            'enabled' => true,
            'resolution' => 'high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Drilldown Enabled
    |--------------------------------------------------------------------------
    |
    | Whether drilldown functionality is enabled for dashboard widgets.
    | Allows users to click on data points to see more detailed information.
    |
    */
    'drilldown_enabled' => [
        'executives' => true,
        'header' => true,
        'manager' => true,
        'supervisor' => true,
        'teacher' => false,
        'student' => false,
        'admin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | KPI Definitions
    |--------------------------------------------------------------------------
    |
    | Definitions of Key Performance Indicators used across dashboards.
    | Each KPI references metrics from the analytics pipeline.
    |
    */
    'kpi_definitions' => [
        'revenue_overview' => [
            'name' => 'Revenue Overview',
            'description' => 'Total revenue, monthly growth, and projections',
            'metrics' => ['total_revenue', 'monthly_revenue_growth', 'revenue_projection'],
            'data_source' => 'finance_analytics',
            'calculation_period' => 'monthly',
            'target_comparison' => true,
        ],
        'student_enrollment_trends' => [
            'name' => 'Student Enrollment Trends',
            'description' => 'Enrollment numbers, growth rates, and retention',
            'metrics' => ['total_students', 'new_enrollments', 'retention_rate'],
            'data_source' => 'lms_analytics',
            'calculation_period' => 'monthly',
            'target_comparison' => true,
        ],
        'department_performance' => [
            'name' => 'Department Performance',
            'description' => 'KPI achievement by department',
            'metrics' => ['kpi_achievement_rate', 'efficiency_score', 'quality_score'],
            'data_source' => 'department_analytics',
            'calculation_period' => 'quarterly',
            'target_comparison' => true,
        ],
        'attendance_summary' => [
            'name' => 'Attendance Summary',
            'description' => 'Overall attendance rates and trends',
            'metrics' => ['average_attendance_rate', 'absenteeism_rate', 'punctuality_rate'],
            'data_source' => 'attendance_analytics',
            'calculation_period' => 'weekly',
            'target_comparison' => true,
        ],
        'inquiry_conversion' => [
            'name' => 'Inquiry Conversion',
            'description' => 'Conversion rates from inquiry to enrollment',
            'metrics' => ['inquiry_to_lead_rate', 'lead_to_sale_rate', 'overall_conversion_rate'],
            'data_source' => 'crm_analytics',
            'calculation_period' => 'monthly',
            'target_comparison' => true,
        ],
        'system_health' => [
            'name' => 'System Health',
            'description' => 'System performance and uptime metrics',
            'metrics' => ['uptime_percentage', 'response_time', 'error_rate'],
            'data_source' => 'system_monitoring',
            'calculation_period' => 'daily',
            'target_comparison' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Dashboard Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions required to access custom dashboard features.
    |
    */
    'custom_dashboard_permissions' => [
        'create_custom_widgets' => ['executives', 'header', 'admin'],
        'modify_layout' => ['executives', 'header', 'manager', 'admin'],
        'export_data' => ['executives', 'header', 'manager', 'supervisor', 'admin'],
        'share_dashboards' => ['executives', 'header', 'manager', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for dashboard alerts and notifications.
    |
    */
    'alert_thresholds' => [
        'kpi_deviation_percentage' => env('DASHBOARD_ALERT_KPI_DEVIATION', 10),
        'attendance_drop_percentage' => env('DASHBOARD_ALERT_ATTENDANCE_DROP', 15),
        'system_error_rate' => env('DASHBOARD_ALERT_ERROR_RATE', 5),
        'revenue_drop_percentage' => env('DASHBOARD_ALERT_REVENUE_DROP', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Privacy Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data privacy and anonymization in dashboards.
    |
    */
    'data_privacy' => [
        'anonymize_student_data' => [
            'enabled' => true,
            'anonymize_after_days' => 90,
            'allowed_roles' => ['executives', 'admin'],
        ],
        'mask_sensitive_financial_data' => [
            'enabled' => true,
            'allowed_roles' => ['executives', 'header', 'manager'],
        ],
        'restrict_personal_data' => [
            'enabled' => true,
            'allowed_fields' => ['name', 'department', 'role'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for mobile-optimized dashboard views.
    |
    */
    'mobile_config' => [
        'enabled' => true,
        'responsive_widgets' => true,
        'simplified_layout' => true,
        'touch_optimized' => true,
        'offline_capable' => false,
        'push_notifications' => [
            'enabled' => true,
            'alert_types' => ['kpi_alerts', 'system_alerts', 'urgent_updates'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Integration settings with external BI and analytics tools.
    |
    */
    'integrations' => [
        'google_analytics' => [
            'enabled' => env('DASHBOARD_GA_ENABLED', false),
            'tracking_id' => env('DASHBOARD_GA_TRACKING_ID'),
            'custom_dimensions' => [
                'user_role',
                'department',
                'dashboard_type',
            ],
        ],
        'power_bi' => [
            'enabled' => env('DASHBOARD_POWER_BI_ENABLED', false),
            'workspace_id' => env('DASHBOARD_POWER_BI_WORKSPACE'),
            'embed_reports' => true,
        ],
        'tableau' => [
            'enabled' => env('DASHBOARD_TABLEAU_ENABLED', false),
            'server_url' => env('DASHBOARD_TABLEAU_SERVER'),
            'site_name' => env('DASHBOARD_TABLEAU_SITE'),
        ],
    ],
];
