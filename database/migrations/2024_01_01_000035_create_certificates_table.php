<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('certificate_ref')->comment('signed PDF reference');
            $table->string('verification_token')->unique();
            $table->timestamp('issued_at');
            $table->json('metadata')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
            $table->index('verification_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
