<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->onDelete('cascade');
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('score', 5, 2)->nullable();
            $table->json('rubric')->nullable()->comment('JSON rubric data');
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'assignment_id']);
            $table->index('quiz_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
