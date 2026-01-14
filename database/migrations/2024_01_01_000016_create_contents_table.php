<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('seo_meta')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'publish_at']);
            $table->fullText(['title', 'body']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
