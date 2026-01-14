<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('course_code', 50);
            $table->string('term', 50);
            $table->string('grade_value', 10);
            $table->decimal('points', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
