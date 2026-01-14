<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Dropping gamification_points table...\n";

if (Schema::hasTable('gamification_points')) {
    Schema::dropIfExists('gamification_points');
    echo "✓ gamification_points table dropped\n";
}

// Delete the migration record if it exists
$deleted = DB::table('migrations')
    ->where('migration', '2024_01_01_000058_create_gamification_points_table')
    ->delete();

if ($deleted) {
    echo "✓ Removed migration record for gamification_points\n";
}

echo "Done! Now run: php artisan migrate\n";
