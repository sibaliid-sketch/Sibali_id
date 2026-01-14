<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_leads', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->integer('score')->default(0);
            $table->enum('stage', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable()->comment('company_size, industry, requirements');
            $table->timestamps();

            $table->index(['stage', 'score']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_leads');
    }
};
