<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('child_role_id')->constrained('roles')->onDelete('cascade');
            $table->string('scope')->nullable();
            $table->timestamps();

            $table->unique(['parent_role_id', 'child_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_hierarchies');
    }
};
