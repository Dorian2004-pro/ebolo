<?php

namespace App\Http\Controllers;

use App\Models\ArtisanProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArtisanController extends Controller
{
    public function index()
    {
        $artisans = ArtisanProfile::with(['user', 'services', 'galleryImages'])
            ->where('status', 'approved')
            ->where('is_verified', true)
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        return response()->json($artisans);
    }

    public function show($id)
    {
        $artisan = ArtisanProfile::with(['user', 'services', 'galleryImages', 'reviews'])
            ->where('status', 'approved')
            ->where('is_verified', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->findOrFail($id);

        return response()->json($artisan);
    }

    public function publicAvailability($id)
    {
        $artisan = ArtisanProfile::where('status', 'approved')
            ->where('is_verified', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->findOrFail($id);

        return response()->json([
            'days' => $artisan->availability ?? [],
            'booked' => $artisan->bookings()
                ->whereIn('status', ['pending', 'confirmed'])
                ->get(['booking_date', 'booking_time'])
                ->map(fn ($booking) => [
                    'date' => $booking->booking_date,
                    'time' => substr($booking->booking_time, 0, 5),
                ]),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('artisanProfile');
        $profile = $user->artisanProfile ?? ArtisanProfile::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'id' => $profile->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'city' => $user->city,
            'specialty' => $profile->specialty,
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'location' => $profile->location,
            'workplace' => $profile->location ?: ($user->city ?: null),
            'avatar_url' => $user->avatar,
            'status' => $profile->status,
            'is_verified' => (bool) $profile->is_verified,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $profile = $user->artisanProfile ?? ArtisanProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'specialty' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
            unset($validated['name']);
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
            unset($validated['phone']);
        }

        if (isset($validated['city'])) {
            $user->city = $validated['city'];
            unset($validated['city']);
        }

        $user->save();

        $profile->fill($validated)->save();

        return response()->json($this->profile($request)->getData(true));
    }

    public function availability(Request $request)
    {
        $profile = $request->user()->artisanProfile ?? ArtisanProfile::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['days' => $profile->availability ?? []]);
    }

    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.day' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.open' => ['required', 'boolean'],
            'days.*.from' => ['nullable', 'date_format:H:i'],
            'days.*.to' => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['days'] as $day) {
            if ($day['open'] && (empty($day['from']) || empty($day['to']) || $day['from'] >= $day['to'])) {
                return response()->json([
                    'message' => 'Chaque jour ouvert doit avoir une plage horaire valide.',
                ], 422);
            }
        }

        $profile = $request->user()->artisanProfile ?? ArtisanProfile::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
        $profile->update(['availability' => $validated['days']]);

        return response()->json(['days' => $profile->availability]);
    }

    public function identityVerification(Request $request)
    {
        $profile = $request->user()->artisanProfile ?? ArtisanProfile::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'status' => $profile->identity_submitted_at
                ? ($profile->status === 'approved' ? 'approved' : ($profile->status === 'rejected' ? 'rejected' : 'pending'))
                : 'none',
            'id_type' => $profile->identity_type,
            'submitted_at' => $profile->identity_submitted_at?->toIso8601String(),
            'rejection_reason' => $profile->identity_rejection_reason,
        ]);
    }

    public function submitIdentityVerification(Request $request)
    {
        $validated = $request->validate([
            'id_type' => ['required', Rule::in(['cni', 'passport', 'residence_permit'])],
            'recto' => ['required', 'file', 'image', 'max:2048'],
            'verso' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        $profile = $request->user()->artisanProfile ?? ArtisanProfile::firstOrCreate(['user_id' => $request->user()->id]);

        $rectoPath = $request->file('recto')->store('identity', 'public');
        $versoPath = $request->file('verso') ? $request->file('verso')->store('identity', 'public') : null;

        $profile->forceFill([
            'identity_type' => $validated['id_type'],
            'identity_recto_path' => $rectoPath,
            'identity_verso_path' => $versoPath,
            'identity_submitted_at' => now(),
            'identity_rejection_reason' => null,
            'status' => 'pending',
            'is_verified' => false,
            'rejection_reason' => null,
            'verified_at' => null,
        ])->save();

        return response()->json([
            'status' => 'pending',
            'id_type' => $validated['id_type'],
            'submitted_at' => $profile->identity_submitted_at->toIso8601String(),
            'rejection_reason' => null,
        ], 201);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role?->slug, ['artisan', 'salon'], true), 403);

        if ($request->user()->artisanProfile) {
            return response()->json($request->user()->artisanProfile, 200);
        }

        $validated = $request->validate([
            'specialty' => 'required|string',
            'bio' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'location' => 'nullable|string',
        ]);

        $profile = ArtisanProfile::create([
            'user_id' => auth()->id(),
            'specialty' => $validated['specialty'],
            'bio' => $validated['bio'] ?? null,
            'experience_years' => $validated['experience_years'] ?? 0,
            'location' => $validated['location'] ?? null,
            'is_verified' => false,
            'rating' => 0,
            'status' => 'pending',
        ]);

        return response()->json($profile, 201);
    }

    public function update(Request $request, $id)
    {
        $profile = ArtisanProfile::findOrFail($id);

        $this->authorize('update', $profile);

        $validated = $request->validate([
            'specialty' => 'sometimes|string',
            'bio' => 'sometimes|nullable|string',
            'experience_years' => 'sometimes|nullable|integer|min:0',
            'location' => 'sometimes|nullable|string',
        ]);

        $profile->update($validated);

        return response()->json($profile);
    }
}
