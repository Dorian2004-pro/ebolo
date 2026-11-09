<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Payout;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();
        $monthStart = $now->startOfMonth();

        // --- Totaux + tendance (30 derniers jours vs 30 jours précédents) -------------------
        $stats = [
            'users' => $this->counter(User::query()),
            'providers' => $this->counter(Provider::query()) + ['pending' => Provider::where('status', 'pending')->count()],
            'bookings' => $this->counter(Booking::query()) + ['today' => Booking::where('created_at', '>=', $today)->count()],
            'services' => ['total' => Service::count()],
            'categories' => ['total' => Category::count()],
        ];

        // --- Finances ------------------------------------------------------------------------
        $completedThisMonth = Booking::where('status', 'completed')->where('completed_at', '>=', $monthStart);
        $completedToday = Booking::where('status', 'completed')->where('completed_at', '>=', $today);

        $finance = [
            // Fonds Mobile Money consignés (séquestre) en attente de validation de la prestation
            'escrow_held' => (float) Transaction::where('transaction_type', 'payment')->where('status', 'held')->sum('amount'),
            'escrow_count' => Transaction::where('transaction_type', 'payment')->where('status', 'held')->count(),
            'gmv_month' => (float) (clone $completedThisMonth)->sum('total_price'),
            'commission_month' => (float) (clone $completedThisMonth)->sum('commission_amount'),
            'revenue_today' => (float) (clone $completedToday)->sum('total_price'),
            'commission_today' => (float) (clone $completedToday)->sum('commission_amount'),
            'payouts_pending_count' => Payout::where('status', 'pending')->count(),
            'payouts_pending_amount' => (float) Payout::where('status', 'pending')->sum('net_amount'),
            'payments_pending' => Transaction::where('status', 'pending')->count(),
        ];

        // --- Séries sur 14 jours (agrégées en PHP : portable entre SQLite / MySQL / PostgreSQL) -
        $days = 14;
        $from = $today->subDays($days - 1);
        $labels = [];
        $bookingsSeries = [];
        $gmvSeries = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->addDays($i);
            $labels[] = $d->locale('fr')->isoFormat('DD MMM');
            $bookingsSeries[$d->toDateString()] = 0;
            $gmvSeries[$d->toDateString()] = 0.0;
        }
        Booking::where('created_at', '>=', $from)->get(['created_at', 'total_price', 'status'])->each(
            function ($b) use (&$bookingsSeries, &$gmvSeries) {
                $key = $b->created_at->toDateString();
                if (! array_key_exists($key, $bookingsSeries)) {
                    return;
                }
                $bookingsSeries[$key]++;
                if ($b->status !== 'cancelled') {
                    $gmvSeries[$key] += (float) $b->total_price;
                }
            }
        );

        $byStatus = Booking::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $bookingStatus = collect(Booking::STATUSES)->map(fn ($s) => ['status' => $s, 'count' => (int) ($byStatus[$s] ?? 0)])->all();

        $topCategories = DB::table('bookings')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->join('categories', 'services.category_id', '=', 'categories.id')
            ->whereNull('bookings.deleted_at')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->select('categories.name as name', DB::raw('count(*) as total'))
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->total]);

        $todo = [
            'pending_providers' => $stats['providers']['pending'],
            'new_users_today' => User::where('created_at', '>=', $today)->count(),
            'completed_today' => (clone $completedToday)->count(),
            'open_disputes' => Dispute::whereIn('status', Dispute::OPEN_STATUSES)->count(),
        ];

        $recent = Booking::with(['customer:id,first_name,last_name', 'provider:id,first_name,last_name', 'service:id,name'])
            ->latest()->limit(6)->get()
            ->map(fn (Booking $b) => BookingController::present($b));

        return response()->json([
            'stats' => $stats,
            'finance' => $finance,
            'series' => ['labels' => $labels, 'bookings' => array_values($bookingsSeries), 'gmv' => array_values($gmvSeries)],
            'booking_status' => $bookingStatus,
            'top_categories' => $topCategories,
            'todo' => $todo,
            'recent_bookings' => $recent,
            'generated_at' => $now->toIso8601String(),
        ]);
    }

    /** total + variation (%) sur 30 jours glissants par rapport aux 30 jours précédents. */
    private function counter($query): array
    {
        $now = now();
        $total = (clone $query)->count();
        $current = (clone $query)->where('created_at', '>=', $now->copy()->subDays(30))->count();
        $previous = (clone $query)->whereBetween('created_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)])->count();

        return [
            'total' => $total,
            'last_30d' => $current,
            'trend' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
        ];
    }
}
