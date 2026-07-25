<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCafeOrderExtrasTable extends Migration
{
    public function up()
    {
        Schema::create('cafe_order_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cafe_order_extras');
    }
}