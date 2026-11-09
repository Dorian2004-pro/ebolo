<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_access_all_integrated_modules(): void
    {
        $this->seed();

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@ebolo.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure(['accessToken', 'expiresAt', 'admin' => ['id', 'email', 'adminRole']]);

        $token = $login->json('accessToken');

        $this->withToken($token)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['stats', 'finance', 'series', 'booking_status', 'todo']);

        foreach (['users', 'providers', 'services', 'bookings', 'payments', 'disputes'] as $module) {
            $this->withToken($token)
                ->getJson('/api/admin/'.$module)
                ->assertOk()
                ->assertJsonStructure(['data', 'meta']);
        }
    }

    public function test_non_admin_cannot_sign_in_to_admin(): void
    {
        $this->seed();

        $this->postJson('/api/admin/auth/login', [
            'email' => 'client@ebolo.com',
            'password' => 'password123',
        ])->assertUnauthorized();
    }

    public function test_public_registration_cannot_create_an_administrator(): void
    {
        $this->seed();

        $this->postJson('/api/register', [
            'name' => 'Unexpected Admin',
            'email' => 'unexpected-admin@ebolo.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ])->assertUnprocessable();
    }

    public function test_archiving_a_user_preserves_its_booking_history(): void
    {
        $this->seed();
        $token = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@ebolo.com',
            'password' => 'password123',
        ])->assertOk()->json('accessToken');
        $user = User::where('email', 'client@ebolo.com')->firstOrFail();

        $this->withToken($token)->deleteJson('/api/admin/users/'.$user->id)->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('bookings', ['user_id' => $user->id]);
    }

    public function test_admin_interface_is_served_by_the_core_application(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

        $this->get('/admin')->assertOk()->assertSee('EBOLO Admin');
    }
}
