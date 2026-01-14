<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimization_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('asset_ref');
            $table->bigInteger('original_size');
            $table->bigInteger('optimized_size');
            $table->decimal('savings_pct', 5, 2);
            $table->timestamp('done_at');

            $table->index(['asset_ref', 'done_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_logs');
    }
};
