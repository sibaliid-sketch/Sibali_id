<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Fixing pending migrations...\n";
echo str_repeat('=', 50)."\n";

// Drop the community_posts table if it exists
if (Schema::hasTable('community_posts')) {
    echo "Dropping community_posts table...\n";
    Schema::dropIfExists('community_posts');
    echo "✓ community_posts table dropped\n";
}

// Delete the migration record if it exists
$deleted = DB::table('migrations')
    ->where('migration', '2024_01_01_000054_create_community_posts_table')
    ->delete();

if ($deleted) {
    echo "✓ Removed migration record for community_posts\n";
}

echo str_repeat('=', 50)."\n";
echo "Done! Now run: php artisan migrate\n";
