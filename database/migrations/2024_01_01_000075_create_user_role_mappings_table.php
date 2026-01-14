<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('scope')->default('global');
            $table->timestamp('created_at');

            $table->unique(['user_id', 'role_id', 'scope']);
            $table->index(['user_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_mappings');
    }
};
