<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('batchs', function (Blueprint $table) {
            $table->integer('amount_used')->default('0');
            $table->integer('amount_reserved')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batchs', function (Blueprint $table) {
            $table->dropColumn('amount_used');
            $table->dropColumn('amount_reserved');
        });
    }
};
