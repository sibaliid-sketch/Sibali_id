<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->integer('capacity')->default(100);
            $table->string('join_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            $table->index(['status', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webinars');
    }
};
