<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_statistics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cache_key');
            $table->bigInteger('hits')->default(0);
            $table->bigInteger('misses')->default(0);
            $table->timestamp('last_updated');

            $table->unique('cache_key');
            $table->index('last_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_statistics');
    }
};
