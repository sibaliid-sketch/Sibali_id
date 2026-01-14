<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_id')->nullable(); // Changed: removed foreign key constraint
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->integer('likes_count')->default(0);
            $table->enum('moderation_state', ['pending', 'approved', 'rejected', 'flagged'])->default('approved');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['community_id', 'moderation_state', 'created_at']);
            $table->fullText('body');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
