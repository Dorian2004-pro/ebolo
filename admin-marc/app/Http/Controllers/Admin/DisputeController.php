<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_disputes');

        $query = Dispute::query()->with(['booking:id,reference', 'raiser:id,first_name,last_name', 'against:id,first_name,last_name']);
        $this->search($query, ['reference', 'reason'], $request->query('search'));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $byStatus = Dispute::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = [
            'all' => (int) $byStatus->sum(),
            'open' => (int) ($byStatus['open'] ?? 0),
            'investigating' => (int) ($byStatus['investigating'] ?? 0),
            'resolved' => (int) ($byStatus['resolved'] ?? 0),
        ];

        $page = $query->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")->latest()->paginate($this->perPage($request));

        return response()->json($this->paginated($page, fn (Dispute $d) => self::present($d)) + ['counts' => $counts]);
    }

    public function update(Request $request, Dispute $dispute): JsonResponse
    {
        $this->requirePermission($request, 'manage_disputes');

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'investigating', 'resolved', 'closed'])],
            'resolution' => ['required_if:status,resolved', 'nullable', 'string', 'max:2000'],
        ]);

        $old = $dispute->only(['status', 'resolution']);

        $dispute->fill($data);
        if (in_array($data['status'], ['resolved', 'closed'], true)) {
            $dispute->resolved_by = $request->user()->id;
            $dispute->resolved_at = now();
        }
        $dispute->save();

        AuditLog::record('dispute_updated', 'dispute', $dispute->id, $old, $data, $this->admin($request)->id);

        return response()->json(['data' => self::present($dispute->load(['booking', 'raiser', 'against']))]);
    }

    public static function present(Dispute $d): array
    {
        $name = fn ($u) => $u ? trim($u->first_name.' '.$u->last_name) : '—';

        return [
            'id' => $d->id,
            'reference' => $d->reference,
            'booking' => $d->booking?->reference,
            'raised_by' => $name($d->raiser),
            'raised_against' => $name($d->against),
            'reason' => $d->reason,
            'reason_type' => $d->reason_type,
            'amount_disputed' => $d->amount_disputed !== null ? (float) $d->amount_disputed : null,
            'status' => $d->status,
            'priority' => $d->priority,
            'resolution' => $d->resolution,
            'created_at' => $d->created_at?->toIso8601String(),
        ];
    }
}
