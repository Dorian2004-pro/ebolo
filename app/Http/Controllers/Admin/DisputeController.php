<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('disputes')
            ->leftJoin('bookings', 'disputes.booking_id', '=', 'bookings.id')
            ->leftJoin('users as raisers', 'disputes.raised_by', '=', 'raisers.id')
            ->leftJoin('users as opponents', 'disputes.raised_against', '=', 'opponents.id')
            ->select([
                'disputes.*',
                'bookings.id as booking_reference',
                'raisers.name as raiser_name',
                'opponents.name as opponent_name',
            ]);

        if ($request->filled('status')) {
            $query->where('disputes.status', $request->query('status'));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->query('search').'%';
            $query->where(fn ($query) => $query
                ->where('disputes.reference', 'like', $term)
                ->orWhere('disputes.reason', 'like', $term));
        }

        $counts = ['all' => DB::table('disputes')->count()];
        foreach (['open', 'investigating', 'resolved'] as $status) {
            $counts[$status] = DB::table('disputes')->where('status', $status)->count();
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page = $query->orderByDesc('disputes.created_at')->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($dispute) => [
                'id' => $dispute->id,
                'reference' => $dispute->reference,
                'booking' => $dispute->booking_reference
                    ? 'EB-'.str_pad((string) $dispute->booking_reference, 6, '0', STR_PAD_LEFT)
                    : null,
                'raised_by' => $dispute->raiser_name ?? '—',
                'raised_against' => $dispute->opponent_name ?? '—',
                'reason' => $dispute->reason,
                'reason_type' => $dispute->reason_type,
                'amount_disputed' => $dispute->amount_disputed === null ? null : (float) $dispute->amount_disputed,
                'priority' => $dispute->priority,
                'status' => $dispute->status,
                'resolution' => $dispute->resolution,
                'created_at' => $dispute->created_at,
            ]),
            'meta' => [
                'page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
            'counts' => $counts,
        ]);
    }

    public function update(Request $request, int $dispute): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['investigating', 'resolved'])],
            'resolution' => ['nullable', 'string', 'max:5000'],
        ]);

        $record = DB::table('disputes')->where('id', $dispute)->first();
        abort_unless($record, 404);
        $resolution = $data['resolution'] ?? null;
        if ($data['status'] === 'resolved' && ! filled($resolution)) {
            return response()->json([
                'message' => 'Une résolution est obligatoire pour clôturer le litige.',
                'errors' => ['resolution' => ['Une résolution est obligatoire.']],
            ], 422);
        }

        DB::table('disputes')->where('id', $dispute)->update([
            'status' => $data['status'],
            'resolution' => $resolution,
            'resolved_by' => $data['status'] === 'resolved' ? $request->user()->id : null,
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Litige mis à jour']);
    }
}
