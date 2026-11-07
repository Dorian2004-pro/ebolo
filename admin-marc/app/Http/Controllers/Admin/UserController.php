<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');

        $query = User::query()->with('roles:id,user_id,role')->withCount('bookingsAsCustomer');

        $this->search($query, ['first_name', 'last_name', 'email', 'phone'], $request->query('search'));

        match ($request->query('status')) {
            'active' => $query->where('is_active', true)->where('is_suspended', false),
            'suspended' => $query->where('is_suspended', true),
            'inactive' => $query->where('is_active', false)->where('is_suspended', false),
            default => null,
        };

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('role', $role));
        }

        $counts = [
            'all' => User::count(),
            'active' => User::where('is_active', true)->where('is_suspended', false)->count(),
            'suspended' => User::where('is_suspended', true)->count(),
            'inactive' => User::where('is_active', false)->where('is_suspended', false)->count(),
        ];

        $page = $query->latest()->paginate($this->perPage($request));

        return response()->json($this->paginated($page, fn (User $u) => self::present($u)) + ['counts' => $counts]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');

        $user->load('roles:id,user_id,role')->loadCount('bookingsAsCustomer');

        return response()->json(['data' => self::present($user, true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'city' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(['customer', 'provider'])],
        ]);

        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data + ['country' => 'Cameroun', 'is_active' => true]);
        $user->roles()->create(['role' => $role]);

        AuditLog::record('user_created', 'user', $user->id, [], ['email' => $user->email, 'role' => $role], $this->admin($request)->id);

        return response()->json(['data' => self::present($user->load('roles'))], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');

        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $old = $user->only(['first_name', 'last_name', 'email', 'phone', 'city']);
        $user->update($data);

        AuditLog::record('user_updated', 'user', $user->id, $old, $user->only(array_keys($old)), $this->admin($request)->id);

        return response()->json(['data' => self::present($user->fresh()->load('roles'))]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');
        $this->guardProtected($request, $user);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user->update(['is_suspended' => true, 'suspension_reason' => $data['reason']]);
        $user->tokens()->delete(); // coupe toutes les sessions API de l'utilisateur

        AuditLog::record('user_suspended', 'user', $user->id, ['is_suspended' => false], ['is_suspended' => true, 'reason' => $data['reason']], $this->admin($request)->id);

        return response()->json(['data' => self::present($user->load('roles'))]);
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');

        $user->update(['is_suspended' => false, 'is_active' => true, 'suspension_reason' => null]);

        AuditLog::record('user_activated', 'user', $user->id, ['is_suspended' => true], ['is_suspended' => false], $this->admin($request)->id);

        return response()->json(['data' => self::present($user->load('roles'))]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'manage_users');
        $this->guardProtected($request, $user);

        $user->tokens()->delete();
        $user->delete(); // suppression logique (deleted_at)

        AuditLog::record('user_deleted', 'user', $user->id, ['email' => $user->email], [], $this->admin($request)->id);

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    /** Un admin ne peut ni se suspendre/supprimer lui-même, ni toucher à un super administrateur. */
    private function guardProtected(Request $request, User $user): void
    {
        abort_if($user->id === $request->user()->id, 422, 'Vous ne pouvez pas effectuer cette action sur votre propre compte.');
        abort_if($user->admin?->admin_role === 'super_admin', 422, 'Un super administrateur ne peut pas être modifié ici.');
    }

    public static function present(User $u, bool $detailed = false): array
    {
        $row = [
            'id' => $u->id,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'full_name' => $u->full_name,
            'email' => $u->email,
            'phone' => $u->phone,
            'city' => $u->city,
            'status' => $u->status,
            'roles' => $u->relationLoaded('roles') ? $u->roles->pluck('role')->values() : [],
            'bookings_count' => $u->bookings_as_customer_count ?? null,
            'created_at' => $u->created_at?->toIso8601String(),
            'last_login_at' => $u->last_login_at?->toIso8601String(),
        ];

        if ($detailed) {
            $row += [
                'address' => $u->address,
                'country' => $u->country,
                'phone_verified' => $u->phone_verified,
                'email_verified' => $u->email_verified,
                'momo_number' => $u->momo_number,
                'momo_provider' => $u->momo_provider,
                'suspension_reason' => $u->suspension_reason,
            ];
        }

        return $row;
    }
}
