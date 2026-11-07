<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('artisan.user')
            ->where('is_active', true)
            ->whereHas('artisan', function ($query) {
                $query->where('status', 'approved')
                    ->where('is_verified', true)
                    ->whereHas('user', fn ($user) => $user->where('status', 'active'));
            })
            ->get();

        return response()->json($services);
    }

    public function show($id)
    {
        $service = Service::with('artisan.user')->findOrFail($id);

        return response()->json($service);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:15',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $service = Service::create([
            'artisan_id' => auth()->user()->artisanProfile?->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'price' => $validated['price'],
            'category' => $validated['category'] ?? 'coiffure',
            'image' => $validated['image'] ?? null,
            'is_active' => true,
        ]);

        return response()->json($service, 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'duration_minutes' => 'sometimes|integer|min:15',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'message' => 'Service supprimé',
        ]);
    }
}
