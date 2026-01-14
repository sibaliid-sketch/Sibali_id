<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('sales_leads')->onDelete('set null');
            $table->foreignId('rep_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->timestamps();

            $table->index(['rep_id', 'start_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_appointments');
    }
};
