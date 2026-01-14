<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('context')->nullable()->comment('class_id, service_type, etc');
            $table->text('content')->comment('encrypted if contains PII');
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('-1 to 1');
            $table->json('tags')->nullable()->comment('NLP extracted topics');
            $table->integer('rating')->nullable()->comment('1-5 or 1-10');
            $table->boolean('anonymous')->default(false);
            $table->timestamps();

            // Removed duplicate foreign key constraint
            $table->index(['context', 'created_at']);
            $table->index('sentiment_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
