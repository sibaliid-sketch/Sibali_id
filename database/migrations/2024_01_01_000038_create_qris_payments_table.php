<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qris_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('qris_code')->unique();
            $table->enum('status', ['pending', 'paid', 'expired', 'failed'])->default('pending');
            $table->json('callback_payload')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['qris_code', 'status']);
            $table->index('reconciled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qris_payments');
    }
};
