<?php

namespace App\Http\Controllers;

use App\Models\ArtisanProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereIn('slug', ['client', 'artisan', 'salon'])),
            ],
            'interests' => ['sometimes', 'array', 'max:9'],
            'interests.*' => ['string', 'in:tresses,perruques,locks,maquillage,manucure,soins,couture,tailoring,retouches'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'status' => 'active',
            'interests' => $validated['interests'] ?? [],
        ]);

        if ($user->role()->whereIn('slug', ['artisan', 'salon'])->exists()) {
            ArtisanProfile::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('role', 'artisanProfile'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->status !== 'active' || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role', 'artisanProfile'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        abort_unless($request->user()->role?->slug === 'client', 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'user' => $request->user()->fresh()->load('role'),
        ]);
    }
}
