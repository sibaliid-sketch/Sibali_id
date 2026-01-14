<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->json('attachments')->nullable()->comment('JSON array of attachment references');
            $table->integer('points')->default(100);
            $table->timestamps();
            $table->softDeletes();

            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
