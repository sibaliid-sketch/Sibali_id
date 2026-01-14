<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->integer('capacity')->default(1);
            $table->string('timezone')->default('Asia/Makassar');
            $table->timestamps();

            $table->index(['resource_id', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_availabilities');
    }
};
