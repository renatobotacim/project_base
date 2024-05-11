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
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('emphasis_value');
            $table->integer('canceled')->default(0);
            $table->timestamp('emphasis_date')->nullable();
            $table->string('emphasis_type',20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {

            $table->dropColumn('canceled');
            $table->dropColumn('emphasis_date');
            $table->dropColumn('emphasis_type');
        });
    }
};
