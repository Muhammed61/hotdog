<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name'=>'Cool Lime','category'=>'HOMEMADE','price'=>160,'desc'=>null],
            ['name'=>'Havuç–Portakal','category'=>'SMOOTHIES','price'=>160,'desc'=>null],
            ['name'=>'Strawberry Milkshake','category'=>'MILKSHAKES','price'=>170,'desc'=>null],
            ['name'=>'Con Panna','category'=>'ESPRESSO BAR','price'=>100,'desc'=>null],
            ['name'=>'Americano','category'=>'ESPRESSO BASED','price'=>140,'desc'=>'Sıcak 140 TL'],
            ['name'=>'Cold Brew','category'=>'SOĞUK DEMLEME','price'=>160,'desc'=>null],
            ['name'=>'Yeşil Elma','category'=>'FROZEN','price'=>160,'desc'=>null],
            ['name'=>'No:9','category'=>'FRAPPES','price'=>170,'desc'=>'Chikoba ve Kavrulmuş Badem aromalı'],
            ['name'=>'Toffee Nut','category'=>'SWEET WINTER','price'=>170,'desc'=>null],
            ['name'=>'Fincan Çay','category'=>'TÜRK KAHVESİ & CLASS','price'=>60,'desc'=>null],
            ['name'=>'Adaçayı','category'=>'BİTKİ ÇAYLARI','price'=>120,'desc'=>null],
            ['name'=>'Magnolia','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Limonata','category'=>'HOMEMADE','price'=>160,'desc'=>null],
            ['name'=>'Karpuz–Nane','category'=>'SMOOTHIES','price'=>170,'desc'=>null],
            ['name'=>'Lotus Milkshake','category'=>'MILKSHAKES','price'=>170,'desc'=>null],
            ['name'=>'Cafe Latte','category'=>'ESPRESSO BASED','price'=>150,'desc'=>'Sıcak 150 TL'],
            ['name'=>'Cold Brew Foam','category'=>'SOĞUK DEMLEME','price'=>160,'desc'=>null],
            ['name'=>'Mango','category'=>'FROZEN','price'=>160,'desc'=>null],
            ['name'=>'No:10','category'=>'FRAPPES','price'=>170,'desc'=>'Toffee karamel ve Pumpkin aromalı'],
            ['name'=>'Pumpkin Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Türk Kahvesi','category'=>'TÜRK KAHVESİ & CLASS','price'=>100,'desc'=>null],
            ['name'=>'Budapest','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Earl Grey Ice Tea','category'=>'HOMEMADE','price'=>160,'desc'=>'Bergamot aromalı soğuk çay'],
            ['name'=>'Chocolate Milkshake','category'=>'MILKSHAKES','price'=>170,'desc'=>null],
            ['name'=>'Lungo','category'=>'ESPRESSO BAR','price'=>80,'desc'=>null],
            ['name'=>'Cappuccino','category'=>'ESPRESSO BASED','price'=>150,'desc'=>null],
            ['name'=>'Orman Meyveli','category'=>'FROZEN','price'=>160,'desc'=>null],
            ['name'=>'Chai Tea Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Double Türk Kahvesi','category'=>'TÜRK KAHVESİ & CLASS','price'=>140,'desc'=>null],
            ['name'=>'Kış Çayı','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'Tatlı','category'=>'TATLILAR','price'=>240,'desc'=>null],
            ['name'=>'Mango Yeşilçay Ice Tea','category'=>'HOMEMADE','price'=>160,'desc'=>null],
            ['name'=>'Mocchista','category'=>'ESPRESSO BAR','price'=>100,'desc'=>null],
            ['name'=>'Caramel Macchiato','category'=>'ESPRESSO BASED','price'=>160,'desc'=>null],
            ['name'=>'Arabako Frozen','category'=>'FROZEN','price'=>160,'desc'=>null],
            ['name'=>'Chocolate Cookie','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Sıcak Çikolata','category'=>'TÜRK KAHVESİ & CLASS','price'=>190,'desc'=>null],
            ['name'=>'Orange Ballos','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'Demlenmiş Ice Tea','category'=>'HOMEMADE','price'=>160,'desc'=>null],
            ['name'=>'Ristretto','category'=>'ESPRESSO BAR','price'=>80,'desc'=>null],
            ['name'=>'Cortado','category'=>'ESPRESSO BASED','price'=>150,'desc'=>null],
            ['name'=>'Cookie Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Beyaz Çikolata','category'=>'TÜRK KAHVESİ & CLASS','price'=>190,'desc'=>null],
            ['name'=>'Papatya','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'Lotus Cup','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Romano','category'=>'ESPRESSO BAR','price'=>60,'desc'=>null],
            ['name'=>'Flat White','category'=>'ESPRESSO BASED','price'=>150,'desc'=>null],
            ['name'=>'Hazelnut Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Sahlep','category'=>'TÜRK KAHVESİ & CLASS','price'=>190,'desc'=>null],
            ['name'=>'Yeşilçay','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'Paris Brest','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Mocha','category'=>'ESPRESSO BASED','price'=>160,'desc'=>'Sıcak 160 TL'],
            ['name'=>'Almond Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Cola-Sprite','category'=>'TÜRK KAHVESİ & CLASS','price'=>70,'desc'=>null],
            ['name'=>'Kuşburnu','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'Brownie','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Double Espresso','category'=>'ESPRESSO BAR','price'=>160,'desc'=>null],
            ['name'=>'Mocha Zebra','category'=>'ESPRESSO BASED','price'=>160,'desc'=>null],
            ['name'=>'Zencefil-Kurabiye Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Sade Soda','category'=>'TÜRK KAHVESİ & CLASS','price'=>70,'desc'=>null],
            ['name'=>'Nane Limon','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'San Sebastian','category'=>'TATLILAR','price'=>250,'desc'=>null],
            ['name'=>'Piccolo','category'=>'ESPRESSO BASED','price'=>150,'desc'=>null],
            ['name'=>'Bali Tahini Latte','category'=>'SWEET WINTER','price'=>160,'desc'=>null],
            ['name'=>'Meyveli Soda','category'=>'TÜRK KAHVESİ & CLASS','price'=>70,'desc'=>null],
            ['name'=>'Hibiskus','category'=>'BİTKİ ÇAYLARI','price'=>150,'desc'=>null],
            ['name'=>'White Mocha','category'=>'ESPRESSO BASED','price'=>160,'desc'=>'Sıcak 160 TL'],
            ['name'=>'Lotus-Badem Latte','category'=>'SWEET WINTER','price'=>170,'desc'=>null],
            ['name'=>'Churchill','category'=>'TÜRK KAHVESİ & CLASS','price'=>90,'desc'=>null],
            ['name'=>'Caramel Latte','category'=>'ESPRESSO BASED','price'=>160,'desc'=>'Sıcak 160 TL'],
            ['name'=>'Aromalı Türk Kahvesi','category'=>'TÜRK KAHVESİ & CLASS','price'=>100,'desc'=>'Sıcak 100 TL, Soğuk 100 TL'],
            ['name'=>'Vanilya Latte','category'=>'ESPRESSO BASED','price'=>160,'desc'=>'Sıcak 160 TL'],
            ['name'=>'Bardak Çay','category'=>'TÜRK KAHVESİ & CLASS','price'=>50,'desc'=>null],
            ['name'=>'Su','category'=>'TÜRK KAHVESİ & CLASS','price'=>50,'desc'=>null],
            // Soğuk varyantlar ayrı ürün olarak
            ['name'=>'Ice Americano','category'=>'ESPRESSO BASED','price'=>150,'desc'=>'Soğuk'],
            ['name'=>'Ice Cafe Latte','category'=>'ESPRESSO BASED','price'=>160,'desc'=>'Soğuk'],
            ['name'=>'Ice Mocha','category'=>'ESPRESSO BASED','price'=>170,'desc'=>'Soğuk'],
            ['name'=>'Ice White Mocha','category'=>'ESPRESSO BASED','price'=>170,'desc'=>'Soğuk'],
            ['name'=>'Ice Caramel Latte','category'=>'ESPRESSO BASED','price'=>170,'desc'=>'Soğuk'],
            ['name'=>'Ice Vanilya Latte','category'=>'ESPRESSO BASED','price'=>170,'desc'=>'Soğuk'],
        ];

        $categoryMap = [];
        foreach ($items as $item) {
            if (!isset($categoryMap[$item['category']])) {
                $category = Category::firstOrCreate(
                    ['name' => $item['category']],
                    ['description' => null, 'is_active' => true]
                );
                $categoryMap[$item['category']] = $category->id;
            }
        }

        foreach ($items as $item) {
            $cid = $categoryMap[$item['category']];
            $purchase = max(0, round($item['price'] * 0.5, 2));
            Product::updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['desc'],
                    'category_id' => $cid,
                    'purchase_price' => $purchase,
                    'sale_price' => $item['price'],
                    'stock_quantity' => 0,
                    'min_stock_level' => 5,
                    'unit' => 'adet',
                    'barcode' => null,
                    'is_active' => true,
                ]
            );
        }

        $existing = Product::pluck('name')->all();
        $desired = array_map(fn($i) => $i['name'], $items);
        $deactivate = array_diff($existing, $desired);
        if (!empty($deactivate)) {
            Product::whereIn('name', $deactivate)->update(['is_active' => false]);
        }
    }
}
