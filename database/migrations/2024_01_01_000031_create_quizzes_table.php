<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['multiple_choice', 'essay', 'mixed', 'audio', 'video']);
            $table->json('settings')->nullable()->comment('timed, randomized, attempts_allowed');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
