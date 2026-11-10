<?php

namespace Tests\Feature;

use App\Models\ArtisanProfile;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtisanWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_registration_creates_a_profile_and_returns_a_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/register', [
            'name' => 'Nouvelle Artisane',
            'email' => 'new-artisan@example.com',
            'phone' => '+237600000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::where('slug', 'artisan')->value('id'),
        ])->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'role', 'artisan_profile'], 'token']);

        $this->assertDatabaseHas('artisan_profiles', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_artisan_can_manage_owned_services_and_availability(): void
    {
        $this->seed();
        $artisan = User::where('email', 'artisan@ebolo.com')->firstOrFail();
        $token = $artisan->createToken('test')->plainTextToken;

        $service = $this->withToken($token)->postJson('/api/artisan/services', [
            'name' => 'Soin signature',
            'description' => 'Soin adapté aux cheveux naturels.',
            'duration_minutes' => 60,
            'price' => 12500,
            'category' => 'soin',
        ])->assertCreated()->assertJsonPath('name', 'Soin signature');

        $this->withToken($token)->getJson('/api/artisan/services')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Soin signature']);

        $days = collect(range(1, 7))->map(fn (int $day) => [
            'day' => $day,
            'open' => $day < 7,
            'from' => $day < 7 ? '08:00' : null,
            'to' => $day < 7 ? '18:00' : null,
        ])->all();

        $this->withToken($token)->putJson('/api/artisan/availability', ['days' => $days])
            ->assertOk()
            ->assertJsonPath('days.0.from', '08:00');

        $this->withToken($token)->getJson('/api/artisan/availability')
            ->assertOk()
            ->assertJsonPath('days.6.open', false);

        $client = User::where('email', 'client@ebolo.com')->firstOrFail();
        $this->actingAs($client)->getJson('/api/artisan/services')->assertForbidden();

        $this->actingAs($artisan)->deleteJson('/api/artisan/services/'.$service->json('id'))->assertOk();
    }

    public function test_artisan_booking_data_matches_the_dashboard_contract_and_identity_upload_is_persisted(): void
    {
        Storage::fake('public');
        $this->seed();

        $artisan = User::where('email', 'artisan@ebolo.com')->firstOrFail();
        $profile = ArtisanProfile::where('user_id', $artisan->id)->firstOrFail();
        $token = $artisan->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonStructure([['id', 'client' => ['id', 'name', 'phone'], 'service' => ['name', 'price'], 'starts_at', 'ends_at', 'payment' => ['status']]]);

        $this->withToken($token)->post('/api/artisan/identity-verification', [
            'id_type' => 'cni',
            'recto' => UploadedFile::fake()->image('recto.jpg'),
            'verso' => UploadedFile::fake()->image('verso.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        $profile->refresh();
        $this->assertSame('cni', $profile->identity_type);
        $this->assertNotNull($profile->identity_submitted_at);
        Storage::disk('public')->assertExists($profile->identity_recto_path);
        Storage::disk('public')->assertExists($profile->identity_verso_path);

        $this->withToken($token)->getJson('/api/artisan/identity-verification')
            ->assertOk()
            ->assertJsonPath('id_type', 'cni')
            ->assertJsonPath('status', 'pending');
    }

    public function test_an_artisan_can_only_reply_to_a_pending_booking_they_own(): void
    {
        $this->seed();
        $artisan = User::where('email', 'artisan@ebolo.com')->firstOrFail();
        $client = User::where('email', 'client@ebolo.com')->firstOrFail();
        $profile = $artisan->artisanProfile;
        $service = Service::firstOrFail();
        $booking = Booking::create([
            'user_id' => $client->id,
            'artisan_id' => $profile->id,
            'service_id' => $service->id,
            'location_type' => 'studio',
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'booking_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => $service->price,
            'payment_status' => 'unpaid',
        ]);
        $token = $artisan->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/bookings/'.$booking->id.'/status', [
            'status' => 'confirmed',
        ])->assertOk()
            ->assertJsonPath('client.id', $client->id)
            ->assertJsonPath('service.name', $service->name);

        $this->withToken($token)->patchJson('/api/bookings/'.$booking->id.'/status', [
            'status' => 'rejected',
        ])->assertUnprocessable();

        $this->withToken($token)->getJson('/api/chat/'.$client->id.'/messages')->assertOk();
    }
}
