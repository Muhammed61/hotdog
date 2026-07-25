<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdatePaymentMethodEnumInCafeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Raw SQL ile enum değiştir
        DB::statement("ALTER TABLE cafe_orders MODIFY COLUMN payment_method ENUM('cash', 'card', 'split') NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE cafe_orders MODIFY COLUMN payment_method ENUM('cash', 'card') NULL");
    }
}