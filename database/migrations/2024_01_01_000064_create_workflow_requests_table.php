<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('workflow_type');
            $table->json('payload');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->integer('current_step')->default(1);
            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index('workflow_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_requests');
    }
};
