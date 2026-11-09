<?php

namespace Tests\Feature;

use App\Models\ArtisanProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedBackendModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approves_a_provider_and_the_provider_service_appears_in_the_client_catalog(): void
    {
        $this->seed();

        $artisanRoleId = Role::where('slug', 'artisan')->value('id');
        $registration = $this->postJson('/api/register', [
            'name' => 'Nouvelle Prestataire',
            'email' => 'new-provider@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $artisanRoleId,
        ])->assertCreated();

        $providerToken = $registration->json('token');
        $profile = ArtisanProfile::where('user_id', $registration->json('user.id'))->firstOrFail();

        $this->withToken($providerToken)->postJson('/api/artisan/services', [
            'name' => 'Tresses partagées',
            'description' => 'Prestation de test sur le catalogue partagé.',
            'duration_minutes' => 90,
            'price' => 15000,
            'category' => 'coiffure',
        ])->assertCreated();

        $adminToken = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@ebolo.com',
            'password' => 'password123',
        ])->assertOk()->json('accessToken');

        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)->getJson('/api/admin/providers')
            ->assertOk()
            ->assertJsonFragment(['id' => $profile->id, 'owner' => 'Nouvelle Prestataire']);

        $this->withToken($adminToken)->postJson('/api/admin/providers/'.$profile->id.'/approve')
            ->assertOk();

        $client = User::where('email', 'client@ebolo.com')->firstOrFail();
        $clientToken = $client->createToken('client-catalog')->plainTextToken;

        $this->app['auth']->forgetGuards();
        $this->withToken($clientToken)->getJson('/api/services')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Tresses partagées',
                'artisan_id' => $profile->id,
            ]);

        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)->getJson('/api/admin/services')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Tresses partagées',
                'provider' => 'Nouvelle Prestataire',
            ]);
    }

    public function test_non_admin_cannot_read_the_admin_api(): void
    {
        $this->seed();
        $client = User::where('email', 'client@ebolo.com')->firstOrFail();
        $token = $client->createToken('client-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/providers')->assertForbidden();
    }
}
