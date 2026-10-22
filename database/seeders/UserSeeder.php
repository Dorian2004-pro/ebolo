<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $clientRole = \App\Models\Role::where('slug', 'client')->first();
        $artisanRole = \App\Models\Role::where('slug', 'artisan')->first();
        $adminRole = \App\Models\Role::where('slug', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'client@ebolo.com'],
            [
                'name' => 'Client Test',
                'password' => Hash::make('password123'),
                'phone' => '+237650000001',
                'role_id' => $clientRole->id,
                'city' => 'Douala',
                'address' => 'Bonaberi',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'artisan@ebolo.com'],
            [
                'name' => 'Marie K.',
                'password' => Hash::make('password123'),
                'phone' => '+237650000002',
                'role_id' => $artisanRole->id,
                'city' => 'Yaoundé',
                'address' => 'Bastos',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@ebolo.com'],
            [
                'name' => 'Admin Ébolo',
                'password' => Hash::make('password123'),
                'phone' => '+237650000003',
                'role_id' => $adminRole->id,
                'city' => 'Douala',
                'address' => 'Akwa',
                'status' => 'active',
            ]
        );
    }
}