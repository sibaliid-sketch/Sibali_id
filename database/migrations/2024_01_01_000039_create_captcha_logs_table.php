<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->enum('challenge_type', ['recaptcha_v2', 'recaptcha_v3', 'hcaptcha', 'simple', 'math'])->default('recaptcha_v3');
            $table->boolean('success_flag')->default(false);
            $table->decimal('score', 3, 2)->nullable()->comment('for recaptcha v3');
            $table->string('action')->nullable();
            $table->timestamp('created_at');

            $table->index(['ip', 'created_at']);
            $table->index(['challenge_type', 'success_flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_logs');
    }
};
