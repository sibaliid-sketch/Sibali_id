<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Dropping feedbacks table...\n";

if (Schema::hasTable('feedbacks')) {
    Schema::dropIfExists('feedbacks');
    echo "✓ feedbacks table dropped\n";
}

// Delete the migration record if it exists
$deleted = DB::table('migrations')
    ->where('migration', '2024_01_01_000056_create_feedbacks_table')
    ->delete();

if ($deleted) {
    echo "✓ Removed migration record for feedbacks\n";
}

echo "Done! Now run: php artisan migrate\n";
