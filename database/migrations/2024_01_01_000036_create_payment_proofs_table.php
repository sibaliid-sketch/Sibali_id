<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->text('proof_url')->comment('encrypted storage path');
            $table->string('checksum', 64);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->boolean('verified_flag')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'verified_flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
