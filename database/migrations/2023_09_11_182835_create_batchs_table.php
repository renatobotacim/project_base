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
        Schema::create('batchs', function (Blueprint $table) {
            $table->id();
            $table->string('batch', 45);
            $table->decimal('value', 10, 2);
            $table->decimal('rate', 10, 2)->nullable();
            $table->integer('amount')->nullable();
            $table->timestamp('date_limit');

            $table->unsignedBigInteger('ticket_event_id');
            $table->foreign('ticket_event_id')->references('id')->on('tickets_events')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batchs');
    }
};
