<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_payments');

        $query = Transaction::query()->with(['booking:id,reference', 'user:id,first_name,last_name']);
        $this->search($query, ['reference'], $request->query('search'));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->query('type')) {
            $query->where('transaction_type', $type);
        }

        $summary = [
            'escrow_held' => (float) Transaction::where('transaction_type', 'payment')->where('status', 'held')->sum('amount'),
            'collected' => (float) Transaction::where('transaction_type', 'payment')->where('status', 'completed')->sum('amount'),
            'paid_out' => (float) Transaction::where('transaction_type', 'payout')->where('status', 'completed')->sum('amount'),
            'refunded' => (float) Transaction::where('transaction_type', 'refund')->where('status', 'completed')->sum('amount'),
            'failed' => Transaction::where('status', 'failed')->count(),
            'pending' => Transaction::where('status', 'pending')->count(),
        ];

        $page = $query->latest()->paginate($this->perPage($request));

        return response()->json($this->paginated($page, fn (Transaction $t) => [
            'id' => $t->id,
            'reference' => $t->reference,
            'booking' => $t->booking?->reference,
            'user' => $t->user ? trim($t->user->first_name.' '.$t->user->last_name) : '—',
            'amount' => (float) $t->amount,
            'type' => $t->transaction_type,
            'direction' => $t->direction,
            'gateway' => $t->payment_gateway,
            'status' => $t->status,
            'failure_reason' => $t->failure_reason,
            'created_at' => $t->created_at?->toIso8601String(),
        ]) + ['summary' => $summary]);
    }
}
