<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class TablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Table::firstOrCreate(
                ['name' => 'Masa ' . $i],
                [
                    'capacity'  => 4,
                    'status'    => Table::STATUS_AVAILABLE, // 'available'
                    'is_active' => true,
                ]
            );
        }
    }
}
