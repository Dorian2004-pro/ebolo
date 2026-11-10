<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_bookings');

        $query = Booking::query()->with(['customer:id,first_name,last_name', 'provider:id,first_name,last_name', 'service:id,name']);

        $this->search($query, ['reference'], $request->query('search'));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('booking_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('booking_date', '<=', $to);
        }

        $byStatus = Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $byStatus->sum()];
        foreach (Booking::STATUSES as $s) {
            $counts[$s] = (int) ($byStatus[$s] ?? 0);
        }

        $page = $query->orderByDesc('booking_date')->orderByDesc('start_time')->paginate($this->perPage($request));

        return response()->json($this->paginated($page, fn (Booking $b) => self::present($b)) + ['counts' => $counts]);
    }

    public static function present(Booking $b): array
    {
        $name = fn ($u) => $u ? trim($u->first_name.' '.$u->last_name) : '—';

        return [
            'id' => $b->id,
            'reference' => $b->reference,
            'customer' => $name($b->customer),
            'provider' => $name($b->provider),
            'service' => $b->service?->name ?? '—',
            'booking_date' => $b->booking_date?->toDateString(),
            'start_time' => substr((string) $b->start_time, 0, 5),
            'location_type' => $b->location_type,
            'status' => $b->status,
            'total_price' => (float) $b->total_price,
            'commission_amount' => (float) $b->commission_amount,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }
}
