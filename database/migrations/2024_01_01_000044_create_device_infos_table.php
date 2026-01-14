<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_hash')->unique();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('last_seen');
            $table->json('metadata')->nullable()->comment('screen_size, timezone, language');
            $table->boolean('trusted')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'last_seen']);
            $table->index('device_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_infos');
    }
};
