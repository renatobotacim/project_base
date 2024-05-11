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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('producer_id');
            $table->foreign('producer_id')->references('id')->on('producers')->onDelete('cascade');

            $table->unsignedBigInteger('user_request_id');
            $table->foreign('user_request_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamp('request');
            $table->decimal('value', 10, 2);
            $table->string('token', 10);
            $table->enum("status", ['pending', 'paid', 'canceled'])->default('pending');
            $table->timestamp('payment');
            $table->string('acount', 45);

            $table->unsignedBigInteger('user_payment_id')->nullable();
            $table->foreign('user_payment_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
