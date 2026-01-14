<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = [
    'community_posts',
    'webinars',
    'feedbacks',
    'rewards',
    'gamification_points',
    'achievements',
    'employee_attendances',
    'sales_leads',
    'role_hierarchies',
    'data_audit_logs',
    'workflow_requests',
    'internal_documents',
    'employee_tasks',
    'internal_analytics',
    'class_bookings',
    'booking_carts',
    'class_schedules',
    'booking_availabilities',
    'sales_inquiries',
    'sales_appointments',
    'staff_levels',
    'user_role_mappings',
];

echo "Checking which tables exist:\n";
echo str_repeat('=', 50)."\n";

foreach ($tables as $table) {
    $exists = Illuminate\Support\Facades\Schema::hasTable($table);
    $status = $exists ? '✓ EXISTS' : '✗ NOT EXISTS';
    echo sprintf("%-30s %s\n", $table, $status);
}

echo str_repeat('=', 50)."\n";
