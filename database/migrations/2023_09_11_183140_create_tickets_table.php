<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket', 45);

            $table->unsignedBigInteger('batch_id');
            $table->foreign('batch_id')->references('id')->on('batchs')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum("status", ['reserved', 'available', 'used', 'won', 'canceled'])->default('reserved');

            $table->unsignedBigInteger('courtesy')->nullable();
            $table->foreign('courtesy')->references('id')->on('users')->onDelete('cascade');

            $table->timestamp('checking')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
