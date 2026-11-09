<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role')->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->isAdmin()) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $expiresAt = now()->addHours(24);
        $token = $user->createToken('admin-panel', ['admin'], $expiresAt)->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'expiresAt' => $expiresAt->toIso8601String(),
            'admin' => $this->present($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['admin' => $this->present($request->user()->load('role'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnecté']);
    }

    private function present(User $user): array
    {
        $name = preg_split('/\s+/', trim($user->name), 2) ?: [];

        return [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $name[0] ?? '',
            'lastName' => $name[1] ?? '',
            'adminRole' => 'admin',
            'permissions' => [
                'manage_users',
                'manage_providers',
                'manage_services',
                'manage_bookings',
                'manage_payments',
                'manage_disputes',
            ],
        ];
    }
}
