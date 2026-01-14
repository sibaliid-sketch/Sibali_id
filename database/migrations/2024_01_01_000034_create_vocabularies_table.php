<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabularies', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->text('meaning');
            $table->json('examples')->nullable();
            $table->string('audio_ref')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->json('metadata')->nullable()->comment('SRS data, pronunciation score history');
            $table->timestamps();

            $table->index(['word', 'difficulty_level']);
            $table->fullText(['word', 'meaning']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
