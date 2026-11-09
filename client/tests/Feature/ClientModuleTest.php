<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientModuleTest extends TestCase
{
    public function test_a_client_can_register_through_the_backend_and_receive_a_local_session(): void
    {
        config(['services.ebolo.client_role_id' => 7]);

        Http::fake([
            '*/register' => Http::response([
                'token' => 'client-api-token',
            ], 201),
            '*/me' => Http::response([
                'user' => [
                    'id' => 12,
                    'name' => 'Vanessa Ebanda',
                    'email' => 'vanessa@example.cm',
                    'role' => ['slug' => 'client'],
                ],
            ]),
        ]);

        $response = $this->postJson(route('client.auth.register'), [
            'name' => 'Vanessa Ebanda',
            'email' => 'vanessa@example.cm',
            'phone' => '+237600000000',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'accepted_terms' => true,
            'account_type' => 'client',
            'interests' => ['tresses', 'soins'],
        ]);

        $response->assertCreated()->assertJsonPath('redirect', route('home'));
        $this->assertSame('client-api-token', session('ebolo_token'));
        $this->assertSame(12, session('ebolo_user.id'));

        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/register')
            && $request['role_id'] === config('services.ebolo.client_role_id')
            && $request['email'] === 'vanessa@example.cm'
            && $request['interests'] === ['tresses', 'soins']
            && $request['password'] === $request['password_confirmation']
        );
    }

    public function test_a_salon_can_register_and_continue_to_the_professional_login(): void
    {
        config([
            'services.ebolo.salon_role_id' => 4,
            'services.ebolo.provider_url' => 'http://127.0.0.1:8004',
        ]);

        Http::fakeSequence()
            ->push(['token' => 'salon-api-token'], 201)
            ->push(['message' => 'Déconnexion réussie'], 200);

        $response = $this->postJson(route('client.auth.register'), [
            'account_type' => 'salon',
            'name' => 'Atelier Ébène',
            'email' => 'atelier@example.cm',
            'phone' => '+237600000001',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'accepted_terms' => true,
            'interests' => ['tresses', 'couture'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('redirect', 'http://127.0.0.1:8004/connexion');
        $this->assertNull(session('ebolo_token'));

        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/register')
            && $request['role_id'] === 4
            && $request['interests'] === ['tresses', 'couture']
        );
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'logout')
        );
    }

    public function test_registration_does_not_open_a_client_session_for_an_artisan_role(): void
    {
        config(['services.ebolo.client_role_id' => 7]);

        Http::fakeSequence()
            ->push(['token' => 'wrong-role-token'], 201)
            ->push(['user' => ['id' => 12, 'role' => ['slug' => 'artisan']]], 200)
            ->push(['message' => 'Déconnexion réussie'], 200);

        $response = $this->postJson(route('client.auth.register'), [
            'name' => 'Vanessa Ebanda',
            'email' => 'vanessa@example.cm',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'accepted_terms' => true,
        ]);

        $response->assertForbidden();
        $this->assertNull(session('ebolo_token'));
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/logout')
            && $request->hasHeader('Authorization', 'Bearer wrong-role-token')
        );
    }

    public function test_registration_fails_closed_when_the_client_role_is_not_configured(): void
    {
        config(['services.ebolo.client_role_id' => 0]);

        $response = $this->postJson(route('client.auth.register'), [
            'name' => 'Vanessa Ebanda',
            'email' => 'vanessa@example.cm',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
            'accepted_terms' => true,
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Configurez l’identifiant du rôle client dans les paramètres Ébolo.');
        Http::assertNothingSent();
    }

    public function test_a_client_can_sign_in_but_another_role_cannot_use_the_client_space(): void
    {
        Http::fakeSequence()
            ->push(['token' => 'artisan-api-token'], 200)
            ->push(['user' => ['id' => 7, 'role' => ['slug' => 'artisan']]], 200)
            ->push(['message' => 'Déconnexion réussie'], 200);

        $response = $this->postJson(route('client.auth.login'), [
            'email' => 'artisan@example.cm',
            'password' => 'correct-horse',
        ]);

        $response->assertForbidden()->assertJsonPath('message', 'Cet espace est réservé aux comptes clients.');
        $this->assertNull(session('ebolo_token'));
    }

    public function test_a_client_can_sign_in_and_receive_a_local_session(): void
    {
        Http::fake([
            '*/login' => Http::response(['token' => 'client-api-token']),
            '*/me' => Http::response([
                'user' => [
                    'id' => 12,
                    'name' => 'Vanessa Ebanda',
                    'role' => ['slug' => 'client'],
                ],
            ]),
        ]);

        $response = $this->postJson(route('client.auth.login'), [
            'email' => 'vanessa@example.cm',
            'password' => 'correct-horse',
        ]);

        $response->assertOk()->assertJsonPath('redirect', route('home'));
        $this->assertSame('client-api-token', session('ebolo_token'));
        $this->assertSame('client', session('ebolo_user.role.slug'));
    }

    public function test_catalog_requests_are_proxied_with_the_server_side_api_token(): void
    {
        Http::fake([
            '*/services' => Http::response([['id' => 3, 'name' => 'Tresses']], 200),
        ]);

        $response = $this->withSession(['ebolo_token' => 'private-token'])->getJson(route('client.data.services'));

        $response->assertOk()->assertJsonPath('0.name', 'Tresses');
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/services')
            && $request->hasHeader('Authorization', 'Bearer private-token')
        );
    }

    public function test_an_authenticated_client_can_request_a_booking(): void
    {
        Http::fake([
            '*/bookings' => Http::response([
                'id' => 29,
                'status' => 'pending',
                'location_type' => 'home',
            ], 201),
        ]);

        $response = $this->withSession(['ebolo_token' => 'private-token'])->postJson(
            route('client.data.bookings.create'),
            [
                'artisan_id' => 8,
                'service_id' => 5,
                'booking_date' => now()->addDay()->toDateString(),
                'booking_time' => '14:30',
                'location_type' => 'home',
                'location_details' => 'Bonapriso, Douala',
                'notes' => 'Merci de confirmer le rendez-vous.',
            ],
        );

        $response->assertCreated()->assertJsonPath('status', 'pending');
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/bookings')
            && $request->hasHeader('Authorization', 'Bearer private-token')
            && $request['artisan_id'] === 8
            && $request['service_id'] === 5
            && $request['location_type'] === 'home'
        );
    }

    public function test_the_catalog_cannot_be_requested_without_a_client_session(): void
    {
        $this->getJson(route('client.data.services'))->assertUnauthorized();

        Http::assertNothingSent();
    }
}
