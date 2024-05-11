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
        Schema::create('producers_history_money', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('producer_id');
            $table->enum("type",['C','D']);
            $table->decimal('value',10,2);
            $table->timestamp('block');
            $table->boolean('libered')->nullable();
            $table->timestamp('libered_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producers_history_money');
    }
};
