<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SCHEMA VERIFICATION ===\n\n";

// Check users table user_type enum
echo "1. Users table user_type column:\n";
$result = DB::select("SHOW COLUMNS FROM users WHERE Field = 'user_type'");
if (! empty($result)) {
    echo '   Type: '.$result[0]->Type."\n";
}

// Check rewards table columns
echo "\n2. Rewards table columns:\n";
$result = DB::select('SHOW COLUMNS FROM rewards');
foreach ($result as $column) {
    echo '   - '.$column->Field.' ('.$column->Type.")\n";
}

echo "\n✅ Schema verification complete\n";
