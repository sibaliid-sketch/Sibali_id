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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // full, incremental, differential
            $table->string('target', 100); // database, storage, config
            $table->boolean('success_flag')->default(false);
            $table->bigInteger('size')->nullable()->comment('Backup size in bytes');
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->text('error_message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index('success_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
