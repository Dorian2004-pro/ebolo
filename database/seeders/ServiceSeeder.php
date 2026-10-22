<?php

namespace Database\Seeders;

use App\Models\ArtisanProfile;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $artisan = ArtisanProfile::first();

        $services = [
            [
                'artisan_id' => $artisan->id,
                'name' => 'Knotless Premium',
                'slug' => 'knotless-premium',
                'description' => 'Pose de tresses sans douleur, finition soyeuse et élégante.',
                'duration_minutes' => 180,
                'price' => 85000,
                'category' => 'coiffure',
                'image' => 'https://images.example.com/service-knotless.jpg',
                'is_active' => true,
            ],
            [
                'artisan_id' => $artisan->id,
                'name' => 'Soin hydratant profond',
                'slug' => 'soin-hydratant-profond',
                'description' => 'Hydratation intensive pour cuir chevelu et cheveux abîmés.',
                'duration_minutes' => 90,
                'price' => 45000,
                'category' => 'soin',
                'image' => 'https://images.example.com/service-soin.jpg',
                'is_active' => true,
            ],
            [
                'artisan_id' => $artisan->id,
                'name' => 'Tresse couture luxe',
                'slug' => 'tresse-couture-luxe',
                'description' => 'Création couture artisanale pour un style premium.',
                'duration_minutes' => 240,
                'price' => 120000,
                'category' => 'couture',
                'image' => 'https://images.example.com/service-couture.jpg',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['slug' => $service['slug']],
                $service
            );
        }
    }
}