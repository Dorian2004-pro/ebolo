<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['user', 'booking']);
        $status = match ($request->query('status')) {
            'completed' => 'paid',
            'held' => 'held',
            default => $request->query('status'),
        };
        if ($status) {
            $query->where('status', $status);
        }
        if ($request->filled('type') && $request->query('type') !== 'payment') {
            $query->whereRaw('1 = 0');
        }
        $this->search($query, ['reference'], $request->query('search'));

        $summary = [
            'escrow_held' => (float) Payment::where('status', 'held')->sum('amount'),
            'collected' => (float) Payment::whereIn('status', ['paid', 'released'])->sum('amount'),
            'paid_out' => (float) Payment::where('status', 'released')->sum('amount'),
            'refunded' => (float) Payment::where('status', 'refunded')->sum('amount'),
            'failed' => Payment::where('status', 'failed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'total' => Payment::count(),
        ];

        return response()->json($this->paginate(
            $request,
            $query->latest(),
            fn (Payment $payment) => $this->present($payment),
            ['summary' => $summary],
        ));
    }

    private function present(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference ?: 'PAY-'.$payment->id,
            'type' => 'payment',
            'user' => $payment->user?->name ?? '—',
            'booking' => $payment->booking
                ? 'EB-'.str_pad((string) $payment->booking->id, 6, '0', STR_PAD_LEFT)
                : null,
            'gateway' => $payment->provider,
            'status' => in_array($payment->status, ['paid', 'released'], true) ? 'completed' : $payment->status,
            'failure_reason' => null,
            'direction' => 'in',
            'amount' => (float) $payment->amount,
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }
}
