<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nisn', 20)->unique()->nullable();
            $table->date('birthdate')->nullable();
            $table->string('school_origin', 200)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('grade_level', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('nisn');
            $table->index('parent_id');
            $table->index('enrollment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
