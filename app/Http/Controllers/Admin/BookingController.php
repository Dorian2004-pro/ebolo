<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user', 'artisan.user', 'service']);
        $counts = ['all' => Booking::count()];

        foreach (['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $status) {
            $counts[$status] = Booking::where('status', $status)->count();
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('booking_date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('booking_date', '<=', $request->query('to'));
        }
        if ($term = trim((string) $request->query('search', ''))) {
            $referenceId = preg_match('/^(?:EB-)?(\d+)$/i', $term, $matches) ? (int) $matches[1] : 0;
            $query->where('id', $referenceId);
        }

        return response()->json($this->paginate(
            $request,
            $query->latest('booking_date'),
            fn (Booking $booking) => $this->present($booking),
            ['counts' => $counts],
        ));
    }

    private function present(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => 'EB-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'customer' => $booking->user?->name ?? '—',
            'provider' => $booking->artisan?->user?->name ?? '—',
            'service' => $booking->service?->name ?? '—',
            'location_type' => $booking->location_type,
            'booking_date' => $booking->booking_date,
            'start_time' => $booking->booking_time,
            'status' => $booking->status,
            'total_price' => (float) $booking->total_price,
            'commission_amount' => 0,
        ];
    }
}
