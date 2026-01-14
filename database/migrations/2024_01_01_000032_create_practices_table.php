<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('answers')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('metadata')->nullable()->comment('spaced repetition, adaptive learning data');
            $table->timestamps();

            $table->index(['user_id', 'quiz_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practices');
    }
};
