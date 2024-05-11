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
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 20);
            $table->string('name', 100);
            $table->string('corporative_name', 100);
            $table->string('state_registration', 45);
            $table->bigInteger('owner_id')->nullable();
            $table->string('bank', 45)->nullable();
            $table->string('agency', 20)->nullable();
            $table->string('account', 20)->nullable();
            $table->string('onwer_account', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producers');
    }
};
