<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'sick', 'remote'])->default('present');
            $table->time('checkin')->nullable();
            $table->time('checkout')->nullable();
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            // Removed duplicate foreign key constraint
            $table->unique(['employee_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};
