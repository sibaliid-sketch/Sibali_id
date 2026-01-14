<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_logs', function (Blueprint $table) {
            $table->id();
            $table->string('layer');
            $table->string('ip', 45);
            $table->text('payload')->nullable();
            $table->string('action');
            $table->timestamp('created_at');
            $table->integer('ttl')->nullable();

            $table->index(['ip', 'created_at']);
            $table->index('layer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_logs');
    }
};
