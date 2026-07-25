<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('system_type'); // 'stock' veya 'cafe'
            $table->string('action'); // 'create', 'update', 'delete', 'view' vb.
            $table->string('model'); // 'Product', 'Sale', 'CafeOrder' vb.
            $table->unsignedBigInteger('model_id')->nullable(); // İlgili modelin ID'si
            $table->text('description'); // Aktivite açıklaması
            $table->json('old_values')->nullable(); // Eski değerler (update işlemleri için)
            $table->json('new_values')->nullable(); // Yeni değerler
            $table->string('ip_address', 45); // IPv4 ve IPv6 desteği
            $table->text('user_agent'); // Tarayıcı ve cihaz bilgisi
            $table->string('device_type')->nullable(); // 'desktop', 'mobile', 'tablet'
            $table->string('browser')->nullable(); // Tarayıcı adı
            $table->string('platform')->nullable(); // İşletim sistemi
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['system_type', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activities');
    }
};