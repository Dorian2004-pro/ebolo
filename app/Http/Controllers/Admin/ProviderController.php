<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\ArtisanProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = ArtisanProfile::with(['user', 'services'])->withCount('reviews');
        $counts = [
            'all' => ArtisanProfile::count(),
            'pending' => ArtisanProfile::where('status', 'pending')->count(),
            'approved' => ArtisanProfile::where('status', 'approved')->count(),
            'rejected' => ArtisanProfile::where('status', 'rejected')->count(),
        ];

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($term = trim((string) $request->query('search', ''))) {
            $query->where(function ($query) use ($term): void {
                $query->where('specialty', 'like', '%'.$term.'%')
                    ->orWhere('location', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%'));
            });
        }

        return response()->json($this->paginate(
            $request,
            $query->latest(),
            fn (ArtisanProfile $provider) => $this->present($provider),
            ['counts' => $counts],
        ));
    }

    public function approve(ArtisanProfile $provider): JsonResponse
    {
        $provider->update([
            'is_verified' => true,
            'status' => 'approved',
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);

        return response()->json(['data' => $this->present($provider->load(['user', 'services'])->loadCount('reviews'))]);
    }

    public function reject(Request $request, ArtisanProfile $provider): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $provider->update([
            'is_verified' => false,
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'verified_at' => null,
        ]);

        return response()->json(['data' => $this->present($provider->load(['user', 'services'])->loadCount('reviews'))]);
    }

    private function present(ArtisanProfile $provider): array
    {
        $user = $provider->user;

        return [
            'id' => $provider->id,
            'business_name' => $provider->specialty ?: $user?->name,
            'owner' => $user?->name ?? '—',
            'city' => $user?->city ?: $provider->location,
            'phone' => $user?->phone,
            'status' => $provider->status,
            'rating' => (float) $provider->rating,
            'total_reviews' => $provider->reviews_count ?? 0,
            'categories' => $provider->specialty ? [$provider->specialty] : [],
            'documents' => [
                'id_card' => filled($provider->identity_recto_path),
                'business_license' => false,
                'tax_certificate' => false,
            ],
            'serves_at_customer_location' => false,
            'rejection_reason' => $provider->rejection_reason,
            'verified_at' => $provider->verified_at?->toIso8601String(),
            'created_at' => $provider->created_at?->toIso8601String(),
        ];
    }
}
