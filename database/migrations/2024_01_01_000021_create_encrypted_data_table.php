<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encrypted_data', function (Blueprint $table) {
            $table->id();
            $table->string('context');
            $table->string('iv');
            $table->longText('ciphertext');
            $table->string('key_reference');
            $table->timestamp('created_at');

            $table->index(['context', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encrypted_data');
    }
};
