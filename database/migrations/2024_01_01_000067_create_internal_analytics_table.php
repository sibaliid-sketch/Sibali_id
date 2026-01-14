<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->decimal('value', 15, 2);
            $table->json('context')->nullable();
            $table->timestamp('recorded_at');

            $table->index(['metric_name', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_analytics');
    }
};
