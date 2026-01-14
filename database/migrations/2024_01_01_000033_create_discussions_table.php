<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('pinned')->default(false);
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->integer('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['topic_id', 'status', 'pinned']);
            $table->fullText(['body']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussions');
    }
};
