<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('brief')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'review', 'approved', 'rejected', 'published'])->default('draft');
            $table->integer('score')->default(0);
            $table->timestamps();

            $table->index(['status', 'score']);
            $table->fullText(['title', 'brief']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_ideas');
    }
};
