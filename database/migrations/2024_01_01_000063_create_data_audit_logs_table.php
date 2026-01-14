<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('operation');
            $table->string('affected_table');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('before')->nullable()->comment('encrypted');
            $table->json('after')->nullable()->comment('encrypted');
            $table->timestamp('created_at');

            $table->index(['actor_id', 'created_at']);
            $table->index(['affected_table', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_audit_logs');
    }
};
