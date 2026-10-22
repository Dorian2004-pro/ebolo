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
            'provider' => 'required|string|in:momo,orange,cash',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'amount' => $booking->total_price,
            'provider' => $validated['provider'],
            'reference' => 'PAY-' . uniqid(),
            'status' => 'pending',
        ]);

        return response()->json($payment, 201);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'status' => 'required|string|in:paid,failed',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        $payment->status = $validated['status'];

        if ($validated['status'] === 'paid') {
            $payment->paid_at = now();
            $booking = $payment->booking;
            $booking->payment_status = 'paid';
            $booking->status = 'confirmed';
            $booking->save();
        }

        $payment->save();

        return response()->json($payment);
    }
}