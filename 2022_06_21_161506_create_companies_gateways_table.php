<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies_gateways', function (Blueprint $table) {

            $table->id();

            $table->string("name")->nullable();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('gateway_id')->nullable();
            $table->foreign('gateway_id')->references('id')->on('payments');


            $table->string("token1")->nullable();
            $table->string("token2")->nullable();
            $table->string("token3")->nullable();

            $table->string("file1")->nullable();
            $table->string("file2")->nullable();
            $table->string("file3")->nullable();

            $table->json("config")->nullable();

            $table->integer("status");
            $table->timestamp("expiration")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies_gateways');
    }
}
