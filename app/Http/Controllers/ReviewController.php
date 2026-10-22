<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user', 'artisan.user', 'service'])
            ->where('is_approved', true)
            ->get();

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'artisan_id' => 'required|exists:artisan_profiles,id',
            'service_id' => 'required|exists:services,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $hasBooked = Booking::where('user_id', auth()->id())
            ->where('artisan_id', $validated['artisan_id'])
            ->where('service_id', $validated['service_id'])
            ->whereIn('status', ['completed', 'confirmed'])
            ->exists();

        if (! $hasBooked) {
            return response()->json([
                'message' => 'Vous devez avoir réservé ce service avant de laisser un avis.'
            ], 403);
        }

        $review = Review::create([
            'user_id' => auth()->id(),
            'artisan_id' => $validated['artisan_id'],
            'service_id' => $validated['service_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => true,
        ]);

        return response()->json($review, 201);
    }
}