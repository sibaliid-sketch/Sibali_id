<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token_hash');
            $table->timestamp('last_seen');
            $table->json('device_meta')->nullable()->comment('os, browser, app_version, push_token');
            $table->string('ip', 45)->nullable();
            $table->boolean('trusted')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'last_seen']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sessions');
    }
};
