<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if ($argc < 2) {
    echo "Usage: php drop_table.php <table_name>\n";
    exit(1);
}

$tableName = $argv[1];
$migrationName = $argv[2] ?? null;

echo "Dropping {$tableName} table...\n";

if (Schema::hasTable($tableName)) {
    Schema::dropIfExists($tableName);
    echo "✓ {$tableName} table dropped\n";
} else {
    echo "ℹ {$tableName} table does not exist\n";
}

if ($migrationName) {
    $deleted = DB::table('migrations')
        ->where('migration', $migrationName)
        ->delete();

    if ($deleted) {
        echo "✓ Removed migration record for {$migrationName}\n";
    }
}

echo "Done!\n";
