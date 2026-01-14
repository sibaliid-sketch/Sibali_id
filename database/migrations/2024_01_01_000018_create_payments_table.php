<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('method');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', ['pending', 'verified', 'rejected', 'refunded'])->default('pending');
            $table->text('proof_url')->nullable()->comment('encrypted');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
