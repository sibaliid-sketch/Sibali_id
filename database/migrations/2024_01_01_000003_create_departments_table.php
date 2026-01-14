<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('head_id')->nullable();
            $table->text('description')->nullable();
            $table->json('contact')->nullable();
            $table->json('default_permissions')->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('head_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
