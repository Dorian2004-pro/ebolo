<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'client', 'slug' => 'client'],
            ['name' => 'artisan', 'slug' => 'artisan'],
            ['name' => 'admin', 'slug' => 'admin'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate($role);
        }
    }
}