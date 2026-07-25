<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraAmountToCafeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->decimal('extra_amount', 8, 2)->default(0)->after('total_amount');
            $table->integer('discount_percentage')->default(0)->after('extra_amount');
            $table->decimal('discount_amount', 8, 2)->default(0)->after('discount_percentage');
            $table->decimal('final_amount', 10, 2)->nullable()->after('discount_amount');
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
            $table->dropColumn(['extra_amount', 'discount_percentage', 'discount_amount', 'final_amount']);
        });
    }
}
