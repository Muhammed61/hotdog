<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Bakım modu ayarını sil (eğer varsa)
        Setting::where('key', 'maintenance_mode')->delete();
        
        $settings = [
            // Genel Ayarlar
            [
                'key' => 'site_name',
                'value' => 'Kafe Stok Takip',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Site Adı',
                'description' => 'Programın üstünde görünecek site adını buraya yazın.'
            ],
            [
                'key' => 'cafe_name',
                'value' => 'Kafe Adı',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Kafe Adı',
                'description' => 'İşletmenizin adını buraya yazın.'
            ],
            [
                'key' => 'cafe_address',
                'value' => 'Kafe Adresi',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Kafe Adresi',
                'description' => 'İşletmenizin adresini buraya yazın.'
            ],
            // Stok Ayarları
            [
                'key' => 'auto_stock_update',
                'value' => '0',
                'group' => 'stock',
                'type' => 'boolean',
                'label' => 'Otomatik Stok Güncelleme',
                'description' => 'Kafe siparişleri "Servis Edildi" durumuna geçtiğinde stok otomatik azalır.'
            ],
            [
                'key' => 'low_stock_alert',
                'value' => '5',
                'group' => 'stock',
                'type' => 'number',
                'label' => 'Düşük Stok Uyarı Seviyesi',
                'description' => 'Minimum stok seviyesi tanımlanmamış ürünler için kullanılır.'
            ],
            // Kafe Ayarları
            [
                'key' => 'auto_print_receipt',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'label' => 'Otomatik Adisyon Fişi',
                'description' => 'Sipariş alındığında otomatik olarak fiş yazdırılır.'
            ]
        ];

        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }
    }
}