<?php

namespace App\Http\Controllers;

use App\Models\ArtisanProfile;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
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
