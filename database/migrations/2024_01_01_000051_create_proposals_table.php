<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_lead_id')->constrained('b2b_leads')->onDelete('cascade');
            $table->string('file_ref')->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'expired'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index(['b2b_lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
