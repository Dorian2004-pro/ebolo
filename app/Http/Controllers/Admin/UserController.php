<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = User::with('role')->withCount('bookings');
        $counts = [
            'all' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
        ];

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $role = match ($request->query('role')) {
            'customer' => 'client',
            'provider' => ['artisan', 'salon'],
            'admin' => 'admin',
            default => null,
        };
        if ($role) {
            $query->whereHas('role', fn ($query) => is_array($role)
                ? $query->whereIn('slug', $role)
                : $query->where('slug', $role));
        }

        $this->search($query, ['name', 'email', 'phone'], $request->query('search'));

        return response()->json($this->paginate(
            $request,
            $query->latest(),
            fn (User $user) => $this->present($user),
            ['counts' => $counts],
        ));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $this->present($user->load('role')->loadCount('bookings'), true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'city' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(['customer', 'provider'])],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $role = Role::where('slug', $data['role'] === 'provider' ? 'artisan' : 'client')->firstOrFail();

            return User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'password' => Hash::make($data['password']),
                'role_id' => $role->id,
                'status' => 'active',
            ])->load('role');
        });

        return response()->json(['data' => $this->present($user)], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_if($user->role?->slug === 'admin', 422, 'Un compte administrateur ne peut pas être modifié ici.');

        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:120'],
            'last_name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('users')->ignore($user->id)],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $name = preg_split('/\s+/', trim($user->name), 2) ?: [];
            $data['name'] = trim(
                ($data['first_name'] ?? $name[0] ?? '').' '.($data['last_name'] ?? $name[1] ?? ''),
            );
            unset($data['first_name'], $data['last_name']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json(['data' => $this->present($user->fresh()->load('role')->loadCount('bookings'))]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        abort_if($user->role?->slug === 'admin', 422, 'Un compte administrateur ne peut pas être suspendu ici.');
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user->update(['status' => 'suspended', 'suspension_reason' => $data['reason']]);
        $user->tokens()->delete();

        return response()->json(['data' => $this->present($user->load('role'))]);
    }

    public function activate(User $user): JsonResponse
    {
        abort_if($user->role?->slug === 'admin', 422, 'Un compte administrateur ne peut pas être modifié ici.');
        $user->update(['status' => 'active', 'suspension_reason' => null]);

        return response()->json(['data' => $this->present($user->load('role'))]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id || $user->role?->slug === 'admin', 422, 'Ce compte ne peut pas être désactivé ici.');
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Compte archivé']);
    }

    private function present(User $user, bool $detailed = false): array
    {
        $parts = preg_split('/\s+/', trim($user->name), 2) ?: [];
        $role = match ($user->role?->slug) {
            'client' => 'customer',
            'artisan' => 'provider',
            'salon' => 'provider',
            default => $user->role?->slug,
        };

        return [
            'id' => $user->id,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'city' => $user->city,
            'status' => $user->status,
            'roles' => $role ? [$role] : [],
            'bookings_count' => $user->bookings_count ?? null,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ] + ($detailed ? [
            'address' => $user->address,
            'suspension_reason' => $user->suspension_reason,
            'email_verified' => (bool) $user->email_verified_at,
        ] : []);
    }
}
