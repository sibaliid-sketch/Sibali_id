<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade');
            $table->string('type', 50);
            $table->json('file_refs')->nullable()->comment('JSON file references');
            $table->integer('version')->default(1);
            $table->json('access_policy')->nullable()->comment('JSON access control');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
