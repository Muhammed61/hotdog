<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToCafeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card'])->nullable()->after('status');
            $table->boolean('is_paid')->default(false)->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'is_paid', 'paid_at']);
        });
    }
}
