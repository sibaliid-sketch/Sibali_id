<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'])->default('instagram');
            $table->text('content');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('metrics')->nullable()->comment('likes, shares, comments, reach');
            $table->string('external_id')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'posted', 'failed'])->default('draft');
            $table->timestamps();

            $table->index(['platform', 'status', 'scheduled_at']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_posts');
    }
};
