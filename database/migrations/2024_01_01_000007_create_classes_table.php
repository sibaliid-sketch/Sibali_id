<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50)->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('schedule')->nullable()->comment('JSON schedule data');
            $table->integer('max_students')->default(20);
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_code');
            $table->index('teacher_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
