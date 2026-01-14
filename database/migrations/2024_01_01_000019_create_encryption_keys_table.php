<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_id')->unique();
            $table->string('key_type');
            $table->text('public_key')->nullable();
            $table->text('encrypted_private_key')->nullable();
            $table->boolean('active_flag')->default(true);
            $table->timestamp('rotated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['key_type', 'active_flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encryption_keys');
    }
};
