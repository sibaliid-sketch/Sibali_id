<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->json('file_refs');
            $table->integer('version')->default(1);
            $table->string('access_scope')->default('private');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'access_scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_documents');
    }
};
