<?php

namespace Database\Seeders;

use App\Models\ArtisanProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArtisanProfileSeeder extends Seeder
{
    public function run(): void
    {
        $artisanUser = User::where('email', 'artisan@ebolo.com')->first();

        ArtisanProfile::firstOrCreate(
            ['user_id' => $artisanUser->id],
            [
                'specialty' => 'Knotless & tresses premium',
                'bio' => 'Coiffeuse spécialisée dans les soins capillaires et les créations sur-mesure.',
                'experience_years' => 8,
                'rating' => 4.9,
                'is_verified' => true,
                'status' => 'approved',
                'verified_at' => now(),
                'location' => 'Bastos, Yaoundé',
                'cover_image' => 'https://images.example.com/artist-cover.jpg',
            ]
        );
    }
}
