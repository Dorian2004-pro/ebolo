<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        $admin = $user ? Admin::where('user_id', $user->id)->first() : null;

        $valid = $user
            && $user->password
            && Hash::check($data['password'], $user->password)
            && $user->is_active
            && ! $user->is_suspended
            && $admin
            && $admin->is_active;

        if (! $valid) {
            AuditLog::record('login_failed', 'admin', null, [], ['email' => $data['email']], null, 'failed', 'Invalid credentials');

            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $expiresAt = now()->addHours(24);
        $token = $user->createToken('admin-panel', ['admin'], $expiresAt)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('login_success', 'admin', $admin->id, [], ['email' => $user->email], $admin->id);

        return response()->json([
            'accessToken' => $token,
            'expiresAt' => $expiresAt->toIso8601String(),
            'admin' => $this->payload($user, $admin),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['admin' => $this->payload($request->user(), $this->admin($request))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnecté']);
    }

    private function payload(User $user, Admin $admin): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'adminRole' => $admin->admin_role,
            'permissions' => $admin->permissions ?? [],
        ];
    }
}
