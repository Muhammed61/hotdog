<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'HOMEMADE',
            'SMOOTHIES',
            'MILKSHAKES',
            'ESPRESSO BAR',
            'ESPRESSO BASED',
            'SOĞUK DEMLEME',
            'FROZEN',
            'FRAPPES',
            'SWEET WINTER',
            'TÜRK KAHVESİ & CLASS',
            'BİTKİ ÇAYLARI',
            'TATLILAR',
        ];

        foreach ($names as $name) {
            Category::updateOrCreate(
                ['name' => $name],
                ['description' => null, 'is_active' => true]
            );
        }

        $toDelete = Category::whereNotIn('name', $names)->get();
        foreach ($toDelete as $cat) {
            $hasProducts = Product::where('category_id', $cat->id)->exists();
            if ($hasProducts) {
                $cat->update(['is_active' => false]);
            } else {
                $cat->delete();
            }
        }
    }
}
