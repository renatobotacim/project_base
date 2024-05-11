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
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('emphasis_date');
            $table->json('emphasis_type')->nullable()->change();
            $table->dateTime('emphasis_date_init')->nullable();
            $table->dateTime('emphasis_date_finish')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('emphasis_type');
            $table->dropColumn('emphasis_date_init');
            $table->dropColumn('emphasis_date_finish');
        });
    }
};
