<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Kullanıcıları oluştur
        $this->call(UserSeeder::class);
        
        // Ayarları oluştur
        $this->call(SettingsSeeder::class);
        
        // Ürün kategorilerini oluştur/güncelle
        $this->call(CategorySeeder::class);
        
        // Ürünleri oluştur/güncelle
        $this->call(ProductSeeder::class);
        
        // Masaları oluştur/güncelle (1..50)
        $this->call(TablesSeeder::class);
    }
}
