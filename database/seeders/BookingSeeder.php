<?php

namespace Database\Seeders;

use App\Models\ArtisanProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'client@ebolo.com')->first();
        $artisan = ArtisanProfile::first();
        $service = Service::first();

        Booking::firstOrCreate(
            [
                'user_id' => $client->id,
                'artisan_id' => $artisan->id,
                'service_id' => $service->id,
                'booking_date' => now()->addDays(2)->format('Y-m-d'),
                'booking_time' => '10:00:00',
            ],
            [
                'location_type' => 'studio',
                'location_details' => 'Salon de Bastos',
                'status' => 'confirmed',
                'total_price' => $service->price,
                'payment_status' => 'paid',
                'notes' => 'Rendez-vous confirmé',
            ]
        );
    }
}