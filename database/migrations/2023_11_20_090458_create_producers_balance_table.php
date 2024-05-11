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
        Schema::create('producers_balance', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('producer_id');
            $table->decimal('balance',10,2)->default(0);
            $table->decimal('balance_block',10,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producers_balance');
    }
};
