<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('points');
            $table->string('reason');
            $table->integer('balance_after');
            $table->unsignedBigInteger('transaction_id')->nullable(); // Changed: removed foreign key constraint to non-existent table
            $table->json('metadata')->nullable()->comment('activity_reference, anti_abuse_stamps');
            $table->timestamp('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_points');
    }
};
