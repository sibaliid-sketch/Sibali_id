<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds the foreign key constraint from corporate_partners to contracts
     * after both tables have been created, resolving the circular dependency issue.
     */
    public function up(): void
    {
        Schema::table('corporate_partners', function (Blueprint $table) {
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corporate_partners', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
        });
    }
};
