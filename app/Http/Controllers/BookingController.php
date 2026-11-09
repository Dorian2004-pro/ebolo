<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class BookingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Booking::with(['user', 'artisan.user', 'service'])
            ->orderByDesc('created_at');

        if ($user && in_array($user->role?->slug, ['artisan', 'salon'], true)) {
            $profileId = $user->artisanProfile?->id;
            $query->where('artisan_id', $profileId ?? 0);
        } elseif ($user?->role?->slug === 'client') {
            $query->where('user_id', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return response()->json($query->with('payment')->get()->map(fn (Booking $booking) => $this->present($booking)));
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'artisan.user', 'service', 'payment'])
            ->findOrFail($id);
        abort_unless(
            $booking->user_id === auth()->id() || $booking->artisan?->user_id === auth()->id(),
            403,
        );

        return response()->json($booking->makeHidden('escrow_code_hash'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role?->slug === 'client', 403);

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
        abort_unless(
            $service->artisan_id === (int) $validated['artisan_id']
                && $service->is_active
                && $service->artisan?->status === 'approved'
                && $service->artisan?->is_verified
                && $service->artisan?->user?->status === 'active',
            422,
            'Cette prestation n’est pas actuellement réservable.',
        );

        $alreadyBooked = Booking::where('artisan_id', $validated['artisan_id'])
            ->where('booking_date', $validated['booking_date'])
            ->where('booking_time', $validated['booking_time'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'Ce créneau est déjà réservé.',
            ], 422);
        }

        $escrowCode = (string) random_int(1000, 9999);
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
        $booking->forceFill(['escrow_code_hash' => Hash::make($escrowCode)])->save();

        return response()->json([
            ...$booking->makeHidden('escrow_code_hash')->toArray(),
            'escrow_code' => $escrowCode,
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,rejected,completed,cancelled',
        ]);

        abort_unless($booking->artisan?->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'pending', 422, 'Cette demande a déjà été traitée.');

        if (! in_array($validated['status'], ['confirmed', 'rejected'], true)) {
            abort(422, 'Statut de réservation invalide.');
        }

        $booking->status = $validated['status'];
        $booking->save();

        return response()->json($this->present($booking->load(['user', 'service', 'payment'])));
    }

    public function releaseEscrow(Request $request, $id)
    {
        $booking = Booking::with(['user', 'service', 'payment', 'artisan'])->findOrFail($id);
        abort_unless($booking->artisan?->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'confirmed', 422, 'Seul un rendez-vous confirmé peut être encaissé.');
        abort_unless(in_array($booking->payment_status, ['paid', 'escrow'], true), 422, 'Le paiement de cette réservation n’est pas consigné.');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        if (! $booking->escrow_code_hash || ! Hash::check($validated['code'], $booking->escrow_code_hash)) {
            return response()->json(['message' => 'Code incorrect. Vérifiez auprès de votre cliente.'], 422);
        }

        $booking->status = 'completed';
        $booking->payment_status = 'released';
        $booking->save();
        $booking->payment?->update(['status' => 'released']);

        return response()->json($this->present($booking->load(['user', 'service', 'payment'])));
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        abort_unless($booking->user_id === auth()->id(), 403);
        $booking->delete();

        return response()->json([
            'message' => 'Réservation supprimée',
        ]);
    }

    private function present(Booking $booking): array
    {
        $startsAt = Carbon::parse($booking->booking_date.' '.$booking->booking_time);
        $paymentStatus = match ($booking->payment_status) {
            'paid', 'escrow' => 'escrow',
            'released' => 'released',
            default => 'pending',
        };

        return [
            'id' => $booking->id,
            'reference' => 'EB-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'booking_date' => $booking->booking_date,
            'booking_time' => substr((string) $booking->booking_time, 0, 5),
            'artisan_id' => $booking->artisan_id,
            'service_id' => $booking->service_id,
            'artisan' => [
                'id' => $booking->artisan?->id,
                'user_id' => $booking->artisan?->user_id,
                'business_name' => $booking->artisan?->specialty,
                'user' => [
                    'id' => $booking->artisan?->user?->id,
                    'name' => $booking->artisan?->user?->name,
                ],
            ],
            'client' => [
                'id' => $booking->user?->id,
                'name' => $booking->user?->name,
                'phone' => $booking->user?->phone,
                'avatar_url' => $booking->user?->avatar,
            ],
            'service' => [
                'id' => $booking->service?->id,
                'name' => $booking->service?->name,
                'category' => $booking->service?->category,
                'price' => (float) ($booking->total_price ?? $booking->service?->price ?? 0),
                'duration_minutes' => $booking->service?->duration_minutes,
            ],
            'location_type' => $booking->location_type,
            'total_price' => (float) $booking->total_price,
            'payment_status' => $paymentStatus,
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $startsAt->copy()->addMinutes($booking->service?->duration_minutes ?? 0)->toIso8601String(),
            'status' => $booking->status,
            'payment' => [
                'method' => $booking->payment?->provider,
                'status' => $paymentStatus,
            ],
        ];
    }
}
