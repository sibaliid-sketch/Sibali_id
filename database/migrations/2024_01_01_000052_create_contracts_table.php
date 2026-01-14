<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->onDelete('set null');
            $table->foreignId('partner_id')->nullable()->constrained('corporate_partners')->onDelete('set null');
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('terms_ref')->nullable()->comment('file reference to contract document');
            $table->enum('status', ['draft', 'pending_signature', 'active', 'expired', 'terminated'])->default('draft');
            $table->json('signatures')->nullable()->comment('e-signature metadata');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
