<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name');
            $table->json('schedule')->nullable()->comment('calendar data, timeslots');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'active', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_plans');
    }
};
