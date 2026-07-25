<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('cash_type', ['stock', 'cafe']); // Stok Takip Kasası veya Kafe Sistemi Kasası
            $table->enum('transaction_type', ['income', 'expense', 'withdrawal']); // Gelir, Gider, Para Çekme
            $table->decimal('amount', 10, 2); // İşlem tutarı
            $table->string('description'); // İşlem açıklaması
            $table->text('notes')->nullable(); // Ek notlar
            $table->string('reference_type')->nullable(); // Referans tipi (sale, purchase, order vb.)
            $table->unsignedBigInteger('reference_id')->nullable(); // Referans ID
            $table->unsignedBigInteger('user_id'); // İşlemi yapan kullanıcı
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['cash_type', 'transaction_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_transactions');
    }
}