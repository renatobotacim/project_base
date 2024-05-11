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
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');
            $table->string('sale', 20)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->string('bag', 20)->nullable();
            $table->string('invoice', 50)->nullable();
            $table->string('invoice_url', 200)->nullable();
            $table->string('payment_id', 50)->nullable();
            $table->decimal('value_final', 10, 2)->nullable();
            $table->enum('status', ['pending', 'paid', 'canceled', 'CONFIRMED'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('sale');
            $table->dropColumn('discount');
            $table->dropColumn('bag');
            $table->dropColumn('invoice');
            $table->dropColumn('invoice_url');
            $table->dropColumn('payment_id');
            $table->dropColumn('value_final');
        });
    }
};
