<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_leads', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->json('contact')->comment('encrypted contact info');
            $table->integer('score')->default(0);
            $table->enum('stage', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('next_follow_up')->nullable();
            $table->timestamps();

            $table->index(['stage', 'assigned_to']);
            $table->index('next_follow_up');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_leads');
    }
};
