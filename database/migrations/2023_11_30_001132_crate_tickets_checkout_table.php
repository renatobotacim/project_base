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
        Schema::create('tickets_checkout', function (Blueprint $table) {
            $table->id();
            $table->string('ticket',50)->nullable();
            $table->string('event',50)->nullable();
            $table->timestamp('checkin')->nullable();
            $table->timestamp('validate')->nullable();
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_checkout');
    }
};
