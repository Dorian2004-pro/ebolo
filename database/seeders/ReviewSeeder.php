<?php

namespace Database\Seeders;

use App\Models\ArtisanProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'client@ebolo.com')->first();
        $artisan = ArtisanProfile::first();
        $service = Service::first();

        Review::firstOrCreate(
            [
                'user_id' => $client->id,
                'artisan_id' => $artisan->id,
                'service_id' => $service->id,
            ],
            [
                'rating' => 5,
                'comment' => 'Très professionnel, service impeccable et très doux.',
                'is_approved' => true,
            ]
        );
    }
}