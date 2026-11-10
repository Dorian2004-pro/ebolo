<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // Données fictives (Douala / Yaoundé) pour visualiser le tableau de bord.
        // Désactiver en production : SEED_DEMO_DATA=false
        if (config('app.seed_demo_data')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
