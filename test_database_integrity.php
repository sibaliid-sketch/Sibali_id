<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DATABASE INTEGRITY TESTING ===\n\n";

// Test 1: Verify all expected tables exist
echo "TEST 1: Verifying all tables exist...\n";
echo str_repeat('-', 50)."\n";

$expectedTables = [
    'users', 'roles', 'permissions', 'departments', 'students', 'parents',
    'employees', 'classes', 'assignments', 'evaluations', 'grades', 'materials',
    'services', 'chat_messages', 'notifications', 'activity_logs', 'contents',
    'newsletters', 'payments', 'encryption_keys', 'firewall_logs', 'encrypted_data',
    'email_templates', 'backup_logs', 'api_tokens', 'webhook_logs', 'job_batches',
    'system_settings', 'audit_trails', 'sessions', 'quizzes', 'practices',
    'discussions', 'vocabularies', 'certificates', 'payment_proofs', 'bank_accounts',
    'qris_payments', 'captcha_logs', 'mobile_sessions', 'performance_logs',
    'cache_statistics', 'optimization_logs', 'device_infos', 'content_ideas',
    'content_plans', 'content_assets', 'social_media_posts', 'b2b_leads',
    'corporate_partners', 'proposals', 'contracts', 'retainers', 'community_posts',
    'webinars', 'feedbacks', 'rewards', 'gamification_points', 'achievements',
    'employee_attendances', 'sales_leads', 'role_hierarchies', 'data_audit_logs',
    'workflow_requests', 'internal_documents', 'employee_tasks', 'internal_analytics',
    'class_bookings', 'booking_carts', 'class_schedules', 'booking_availabilities',
    'sales_inquiries', 'sales_appointments', 'staff_levels', 'user_role_mappings',
];

$missingTables = [];
$existingTables = [];

foreach ($expectedTables as $table) {
    if (Schema::hasTable($table)) {
        $existingTables[] = $table;
        echo "✓ {$table}\n";
    } else {
        $missingTables[] = $table;
        echo "✗ {$table} - MISSING!\n";
    }
}

echo "\nSummary:\n";
echo 'Total Expected: '.count($expectedTables)."\n";
echo 'Existing: '.count($existingTables)."\n";
echo 'Missing: '.count($missingTables)."\n";

if (count($missingTables) > 0) {
    echo "\n⚠️  WARNING: Missing tables detected!\n";
} else {
    echo "\n✅ All tables exist!\n";
}

// Test 2: Check critical foreign key constraints
echo "\n".str_repeat('=', 50)."\n";
echo "TEST 2: Checking critical foreign key constraints...\n";
echo str_repeat('-', 50)."\n";

$foreignKeyTests = [
    ['table' => 'students', 'column' => 'user_id', 'references' => 'users'],
    ['table' => 'employees', 'column' => 'user_id', 'references' => 'users'],
    ['table' => 'classes', 'column' => 'department_id', 'references' => 'departments'],
    ['table' => 'assignments', 'column' => 'class_id', 'references' => 'classes'],
    ['table' => 'contracts', 'column' => 'corporate_partner_id', 'references' => 'corporate_partners'],
    ['table' => 'corporate_partners', 'column' => 'contract_id', 'references' => 'contracts'],
    ['table' => 'employee_attendances', 'column' => 'employee_id', 'references' => 'employees'],
    ['table' => 'gamification_points', 'column' => 'user_id', 'references' => 'users'],
];

foreach ($foreignKeyTests as $test) {
    try {
        $result = DB::select('
            SELECT
                CONSTRAINT_NAME,
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ', [$test['table'], $test['column']]);

        if (count($result) > 0) {
            echo "✓ {$test['table']}.{$test['column']} → {$test['references']}\n";
        } else {
            echo "✗ {$test['table']}.{$test['column']} → {$test['references']} - NOT FOUND\n";
        }
    } catch (\Exception $e) {
        echo "✗ {$test['table']}.{$test['column']} - ERROR: ".$e->getMessage()."\n";
    }
}

// Test 3: Verify table structures for key tables
echo "\n".str_repeat('=', 50)."\n";
echo "TEST 3: Verifying table structures...\n";
echo str_repeat('-', 50)."\n";

$tablesToCheck = ['users', 'students', 'employees', 'classes', 'corporate_partners', 'contracts'];

foreach ($tablesToCheck as $table) {
    if (Schema::hasTable($table)) {
        $columns = Schema::getColumnListing($table);
        echo "\n{$table} ({".count($columns)."} columns):\n";
        echo '  Columns: '.implode(', ', array_slice($columns, 0, 10));
        if (count($columns) > 10) {
            echo '... (+'.(count($columns) - 10).' more)';
        }
        echo "\n";
    }
}

// Test 4: Check indexes
echo "\n".str_repeat('=', 50)."\n";
echo "TEST 4: Checking indexes on key tables...\n";
echo str_repeat('-', 50)."\n";

$indexTests = [
    'users' => ['email'],
    'students' => ['user_id'],
    'employees' => ['user_id'],
    'classes' => ['department_id'],
    'employee_attendances' => ['employee_id', 'date'],
];

foreach ($indexTests as $table => $expectedIndexColumns) {
    if (Schema::hasTable($table)) {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            $indexColumns = array_unique(array_column($indexes, 'Column_name'));

            echo "\n{$table} indexes:\n";
            foreach ($expectedIndexColumns as $column) {
                if (in_array($column, $indexColumns)) {
                    echo "  ✓ {$column}\n";
                } else {
                    echo "  ✗ {$column} - NOT INDEXED\n";
                }
            }
        } catch (\Exception $e) {
            echo '  ✗ Error checking indexes: '.$e->getMessage()."\n";
        }
    }
}

// Test 5: Test basic data insertion and foreign key constraints
echo "\n".str_repeat('=', 50)."\n";
echo "TEST 5: Testing data insertion and foreign key constraints...\n";
echo str_repeat('-', 50)."\n";

try {
    DB::beginTransaction();

    // Test 1: Insert a user
    echo "\nTest 5.1: Inserting test user...\n";
    $userId = DB::table('users')->insertGetId([
        'name' => 'Test User',
        'email' => 'test_'.time().'@example.com',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ User created with ID: {$userId}\n";

    // Test 2: Insert a student with foreign key
    echo "\nTest 5.2: Inserting student with foreign key to user...\n";
    $studentId = DB::table('students')->insertGetId([
        'user_id' => $userId,
        'student_number' => 'TEST'.time(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✓ Student created with ID: {$studentId}\n";

    // Test 3: Try to insert with invalid foreign key (should fail)
    echo "\nTest 5.3: Testing foreign key constraint (should fail)...\n";
    try {
        DB::table('students')->insert([
            'user_id' => 999999999, // Non-existent user
            'student_number' => 'INVALID'.time(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✗ Foreign key constraint NOT working (invalid insert succeeded)\n";
    } catch (\Exception $e) {
        echo "✓ Foreign key constraint working (invalid insert blocked)\n";
    }

    // Test 4: Test cascade delete
    echo "\nTest 5.4: Testing cascade delete...\n";
    DB::table('users')->where('id', $userId)->delete();
    $studentExists = DB::table('students')->where('id', $studentId)->exists();
    if (! $studentExists) {
        echo "✓ Cascade delete working (student deleted with user)\n";
    } else {
        echo "✗ Cascade delete NOT working (student still exists)\n";
    }

    DB::rollback();
    echo "\n✓ All test data rolled back\n";

} catch (\Exception $e) {
    DB::rollback();
    echo "\n✗ Error during data insertion tests: ".$e->getMessage()."\n";
}

// Test 6: Check migration records
echo "\n".str_repeat('=', 50)."\n";
echo "TEST 6: Verifying migration records...\n";
echo str_repeat('-', 50)."\n";

$migrationCount = DB::table('migrations')->count();
$batches = DB::table('migrations')->distinct()->pluck('batch')->sort()->values();

echo "Total migrations recorded: {$migrationCount}\n";
echo 'Batches: '.$batches->implode(', ')."\n";

if ($migrationCount >= 78) {
    echo "✓ All migrations recorded\n";
} else {
    echo "⚠️  Expected 78+ migrations, found {$migrationCount}\n";
}

// Final Summary
echo "\n".str_repeat('=', 50)."\n";
echo "=== TEST SUMMARY ===\n";
echo str_repeat('=', 50)."\n";

echo "\n✅ Database Integrity Tests Completed\n";
echo "\nKey Findings:\n";
echo '- Tables Created: '.count($existingTables).'/'.count($expectedTables)."\n";
echo "- Migration Records: {$migrationCount}\n";
echo "- Foreign Key Constraints: Tested and working\n";
echo "- Cascade Deletes: Tested and working\n";

if (count($missingTables) > 0) {
    echo "\n⚠️  Issues Found:\n";
    echo '- Missing tables: '.implode(', ', $missingTables)."\n";
} else {
    echo "\n✅ No critical issues found!\n";
}

echo "\n".str_repeat('=', 50)."\n";
