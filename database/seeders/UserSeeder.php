<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Admin kullanıcısı
        User::firstOrCreate(
            ['email' => 'admin@cafe.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Manager kullanıcısı
        User::firstOrCreate(
            ['email' => 'manager@cafe.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('123456'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );

        // Waiter kullanıcısı
        User::firstOrCreate(
            ['email' => 'garson@cafe.com'],
            [
                'name' => 'Garson',
                'password' => Hash::make('123456'),
                'role' => 'waiter',
                'is_active' => true,
            ]
        );

        // Cashier kullanıcısı
        User::firstOrCreate(
            ['email' => 'kasiyer@cafe.com'],
            [
                'name' => 'Kasiyer',
                'password' => Hash::make('123456'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        // Warehouse Manager kullanıcısı
        User::firstOrCreate(
            ['email' => 'depo@cafe.com'],
            [
                'name' => 'Depo Yöneticisi',
                'password' => Hash::make('123456'),
                'role' => 'warehouse_manager',
                'is_active' => true,
            ]
        );
    }
}