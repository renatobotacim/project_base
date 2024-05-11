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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 45); //hash code
            $table->string('name', 255);
            $table->string('slug', 255)->nullable();
            $table->string('local', 100);

            $table->timestamp('date');
            $table->timestamp('scheduling');
            $table->string('banner')->nullable();
            $table->integer('courtesies')->default(0);
            $table->integer('classification');

            $table->unsignedBigInteger('address_id');
            $table->foreign('address_id')->references('id')->on('address')->onDelete('cascade');

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            $table->unsignedBigInteger('producer_id');
            $table->foreign('producer_id')->references('id')->on('producers')->onDelete('cascade');

            $table->longText('description');

            $table->decimal('emphasis_value',10,2)->nullable(); //destaques do evento
            $table->decimal('emphasis_rate',10,2)->nullable(); //destaques do evento

            $table->unsignedBigInteger('maps_id');
            $table->foreign('maps_id')->references('id')->on('maps')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
