<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('metric_type');
            $table->decimal('value', 10, 2);
            $table->json('context')->nullable()->comment('endpoint, user_id, device_type');
            $table->timestamp('created_at');

            $table->index(['metric_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_logs');
    }
};
