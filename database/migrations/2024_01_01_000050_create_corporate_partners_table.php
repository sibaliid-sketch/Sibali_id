<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->json('contact_info')->nullable()->comment('email, phone, address');
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->json('entitlements')->nullable()->comment('seat_limits, discount_tiers, features');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_partners');
    }
};
