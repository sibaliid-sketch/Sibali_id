<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_assets', function (Blueprint $table) {
            $table->id();
            $table->enum('asset_type', ['image', 'video', 'audio', 'document', 'other'])->default('image');
            $table->string('storage_ref');
            $table->string('checksum', 64);
            $table->string('alt_text')->nullable();
            $table->json('metadata')->nullable()->comment('dimensions, duration, cdn_urls');
            $table->timestamps();

            $table->index(['asset_type', 'created_at']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_assets');
    }
};
