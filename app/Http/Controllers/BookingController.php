<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['user', 'artisan.user', 'service'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($bookings);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'artisan.user', 'service', 'payment'])
            ->findOrFail($id);

        return response()->json($booking);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'artisan_id' => 'required|exists:artisan_profiles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            'location_type' => 'required|string',
            'location_details' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $alreadyBooked = Booking::where('artisan_id', $validated['artisan_id'])
            ->where('booking_date', $validated['booking_date'])
            ->where('booking_time', $validated['booking_time'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'Ce créneau est déjà réservé.'
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'artisan_id' => $validated['artisan_id'],
            'service_id' => $validated['service_id'],
            'location_type' => $validated['location_type'],
            'location_details' => $validated['location_details'] ?? null,
            'booking_date' => $validated['booking_date'],
            'booking_time' => $validated['booking_time'],
            'status' => 'pending',
            'total_price' => $service->price,
            'payment_status' => 'unpaid',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($booking, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,rejected,completed,cancelled',
        ]);

        $booking->status = $validated['status'];
        $booking->save();

        return response()->json($booking);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Réservation supprimée',
        ]);
    }
}