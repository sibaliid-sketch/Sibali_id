<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('fee', 12, 2);
            $table->enum('status', ['active', 'suspended', 'expired', 'cancelled'])->default('active');
            $table->json('terms')->nullable()->comment('service scope, deliverables');
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retainers');
    }
};
