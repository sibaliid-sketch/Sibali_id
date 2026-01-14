<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('points_cost');
            $table->enum('type', ['voucher', 'discount', 'merchandise', 'service', 'other'])->default('voucher');
            $table->json('metadata')->nullable()->comment('terms, validity, stock');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['type', 'active']);
            $table->index('points_cost');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
