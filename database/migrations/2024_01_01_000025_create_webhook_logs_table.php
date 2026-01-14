<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->text('payload')->comment('Encrypted payload');
            $table->string('method', 10)->default('POST');
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['url', 'created_at']);
            $table->index('status_code');
            $table->index('attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
