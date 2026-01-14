<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('staff_level')->default(1);
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->date('hired_at')->nullable();
            $table->string('payroll_number', 50)->unique()->nullable();
            $table->string('bank_account_id', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('staff_level');
            $table->index('department_id');
            $table->index('payroll_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
