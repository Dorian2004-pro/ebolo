<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_providers');

        $query = Provider::query()
            ->join('users', 'users.id', '=', 'providers.user_id')
            ->select('providers.*')
            ->with(['user:id,first_name,last_name,phone,email', 'categories:id,name']);

        $this->search($query, ['providers.business_name', 'users.first_name', 'users.last_name', 'providers.business_city'], $request->query('search'));

        if ($status = $request->query('status')) {
            $query->where('providers.status', $status);
        }

        $counts = Provider::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // « En attente » en premier : c'est le travail à faire pour l'équipe de vérification.
        $page = $query->orderByRaw("CASE WHEN providers.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('providers.created_at')
            ->paginate($this->perPage($request, 12));

        return response()->json($this->paginated($page, fn (Provider $p) => self::present($p)) + [
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
        ]);
    }

    public function approve(Request $request, Provider $provider): JsonResponse
    {
        $this->requirePermission($request, 'manage_providers');

        $old = ['status' => $provider->status];
        $provider->update(['status' => 'approved', 'verified_at' => now(), 'rejected_at' => null, 'rejection_reason' => null]);

        if ($provider->user_id && ! $provider->user->roles()->where('role', 'provider')->exists()) {
            $provider->user->roles()->create(['role' => 'provider']);
        }

        $this->notify($provider, 'Profil validé', 'Félicitations ! Votre profil prestataire a été validé. Vous pouvez recevoir des réservations.', 'provider_approved');
        AuditLog::record('provider_approved', 'provider', $provider->id, $old, ['status' => 'approved'], $this->admin($request)->id);

        return response()->json(['data' => self::present($provider->load(['user', 'categories']))]);
    }

    public function reject(Request $request, Provider $provider): JsonResponse
    {
        $this->requirePermission($request, 'manage_providers');

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = ['status' => $provider->status];
        $provider->update(['status' => 'rejected', 'rejected_at' => now(), 'rejection_reason' => $data['reason'], 'verified_at' => null]);

        $this->notify($provider, 'Profil non validé', 'Votre dossier n\'a pas pu être validé : '.$data['reason'], 'provider_rejected');
        AuditLog::record('provider_rejected', 'provider', $provider->id, $old, ['status' => 'rejected', 'reason' => $data['reason']], $this->admin($request)->id);

        return response()->json(['data' => self::present($provider->load(['user', 'categories']))]);
    }

    private function notify(Provider $provider, string $title, string $message, string $type): void
    {
        if ($provider->user_id) {
            AppNotification::create([
                'user_id' => $provider->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'entity_type' => 'provider',
                'entity_id' => $provider->id,
            ]);
        }
    }

    public static function present(Provider $p): array
    {
        $u = $p->user;

        return [
            'id' => $p->id,
            'business_name' => $p->business_name,
            'owner' => $u ? trim($u->first_name.' '.$u->last_name) : '—',
            'phone' => $u?->phone,
            'email' => $u?->email,
            'city' => $p->business_city,
            'bio' => $p->bio,
            'years_of_experience' => $p->years_of_experience,
            'rating' => (float) $p->rating,
            'total_reviews' => (int) $p->total_reviews,
            'status' => $p->status,
            'categories' => $p->categories->pluck('name')->values(),
            'serves_at_customer_location' => (bool) $p->serves_at_customer_location,
            'documents' => [
                'id_card' => (bool) $p->id_card_url,
                'business_license' => (bool) $p->business_license_url,
                'tax_certificate' => (bool) $p->tax_certificate_url,
            ],
            'mobile_money_provider' => $p->mobile_money_provider,
            'rejection_reason' => $p->rejection_reason,
            'verified_at' => $p->verified_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }
}
