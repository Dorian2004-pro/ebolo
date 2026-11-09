<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'provider' => 'required|string|in:momo,orange',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'pending', 422, 'Cette réservation ne peut plus être payée.');
        if ($booking->payment) {
            abort_unless($booking->payment->status === 'pending', 422, 'Un paiement existe déjà pour cette réservation.');

            return response()->json($booking->payment, 200);
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'amount' => $booking->total_price,
            'provider' => $validated['provider'],
            'reference' => 'PAY-'.uniqid(),
            'status' => 'pending',
        ]);

        return response()->json($payment, 201);
    }

    public function confirm(Request $request)
    {
        abort_unless(config('services.ebolo.payment_mode') === 'demo', 503, 'Aucun prestataire de paiement réel n’est configuré. Le mode de paiement de démonstration est désactivé.');

        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $payment = Payment::with('booking')->findOrFail($validated['payment_id']);
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'pending', 422, 'Ce paiement a déjà été traité.');
        abort_unless($payment->booking?->status === 'pending', 422, 'Cette réservation ne peut plus être payée.');

        $payment->status = 'held';
        $payment->paid_at = now();
        $payment->booking->payment_status = 'escrow';
        $payment->booking->status = 'confirmed';
        $payment->booking->save();
        $payment->booking->makeHidden('escrow_code_hash');

        $payment->save();

        return response()->json([
            'payment' => $payment,
            'mode' => 'demo',
            'message' => 'Paiement simulé : aucun montant réel n’a été débité.',
        ]);
    }
}
