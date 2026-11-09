<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtisanProfile;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = today();
        $month = now()->startOfMonth();
        $bookingsByDay = Booking::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, SUM(total_price) as gmv')
            ->whereDate('created_at', '>=', today()->subDays(13))
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $labels = [];
        $bookingSeries = [];
        $gmvSeries = [];
        for ($offset = 13; $offset >= 0; $offset--) {
            $day = today()->subDays($offset);
            $entry = $bookingsByDay->get($day->toDateString());
            $labels[] = $day->format('d/m');
            $bookingSeries[] = (int) ($entry->count ?? 0);
            $gmvSeries[] = (float) ($entry->gmv ?? 0);
        }

        $bookingStatus = Booking::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($entry) => ['status' => $entry->status, 'count' => (int) $entry->count]);

        $topCategories = Service::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($entry) => ['name' => $entry->category, 'count' => (int) $entry->count]);

        $pendingProviders = ArtisanProfile::where('status', 'pending')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $heldPayments = Payment::where('status', 'held');
        $completedToday = Booking::where('status', 'completed')->whereDate('updated_at', $today)->count();
        $recentBookings = Booking::with(['user', 'artisan.user', 'service'])
            ->latest('booking_date')
            ->limit(6)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'reference' => 'EB-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'customer' => $booking->user?->name ?? '—',
                'provider' => $booking->artisan?->user?->name ?? '—',
                'service' => $booking->service?->name ?? '—',
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->booking_time,
                'status' => $booking->status,
                'total_price' => (float) $booking->total_price,
            ]);

        return response()->json([
            'stats' => [
                'users' => [
                    'total' => User::count(),
                    'trend' => 0,
                    'last_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
                ],
                'providers' => [
                    'total' => ArtisanProfile::count(),
                    'pending' => $pendingProviders,
                    'trend' => 0,
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'trend' => 0,
                    'today' => Booking::whereDate('booking_date', $today)->count(),
                ],
                'services' => ['total' => Service::count()],
                'categories' => ['total' => Service::whereNotNull('category')->distinct()->count('category')],
            ],
            'finance' => [
                'escrow_held' => (float) $heldPayments->sum('amount'),
                'escrow_count' => (int) $heldPayments->count(),
                'collected' => (float) Payment::whereIn('status', ['paid', 'released'])->sum('amount'),
                'paid_out' => (float) Payment::where('status', 'released')->sum('amount'),
                'refunded' => (float) Payment::where('status', 'refunded')->sum('amount'),
                'commission_month' => 0,
                'commission_today' => 0,
                'payouts_pending_amount' => 0,
                'payouts_pending_count' => 0,
                'gmv_month' => (float) Payment::whereIn('status', ['held', 'paid', 'released'])->where('created_at', '>=', $month)->sum('amount'),
                'payments_pending' => $pendingPayments,
                'paid_count' => Payment::whereIn('status', ['paid', 'released'])->count(),
                'revenue_today' => (float) Booking::where('status', 'completed')->whereDate('updated_at', $today)->sum('total_price'),
            ],
            'series' => ['labels' => $labels, 'bookings' => $bookingSeries, 'gmv' => $gmvSeries],
            'booking_status' => $bookingStatus,
            'top_categories' => $topCategories,
            'recent_bookings' => $recentBookings,
            'todo' => [
                'pending_providers' => $pendingProviders,
                'open_disputes' => DB::table('disputes')->where('status', 'open')->count(),
                'new_users_today' => User::whereDate('created_at', $today)->count(),
                'completed_today' => $completedToday,
            ],
        ]);
    }
}
