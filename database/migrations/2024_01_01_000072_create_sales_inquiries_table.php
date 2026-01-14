<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_inquiries', function (Blueprint $table) {
            $table->id();
            $table->json('contact_info')->comment('encrypted');
            $table->string('source');
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_inquiries');
    }
};
