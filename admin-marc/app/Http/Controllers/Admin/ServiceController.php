<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'manage_services');

        $query = Service::query()->with(['category:id,name', 'provider:id,first_name,last_name']);
        $this->search($query, ['name', 'short_description'], $request->query('search'));

        if ($cat = $request->query('category')) {
            $query->where('category_id', $cat);
        }
        if (in_array($request->query('active'), ['1', '0'], true)) {
            $query->where('is_active', $request->query('active') === '1');
        }

        $page = $query->orderByDesc('is_featured')->latest()->paginate($this->perPage($request));

        return response()->json($this->paginated($page, fn (Service $s) => self::present($s)) + [
            'categories' => Category::orderBy('display_order')->get(['id', 'name']),
        ]);
    }

    /** Active / désactive un service, ou le met en avant (is_featured). */
    public function update(Request $request, Service $service): JsonResponse
    {
        $this->requirePermission($request, 'manage_services');

        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        $old = $service->only(array_keys($data));
        $service->update($data);

        AuditLog::record('service_updated', 'service', $service->id, $old, $data, $this->admin($request)->id);

        return response()->json(['data' => self::present($service->load(['category', 'provider']))]);
    }

    public static function present(Service $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'short_description' => $s->short_description,
            'category' => $s->category?->name,
            'provider' => $s->provider ? trim($s->provider->first_name.' '.$s->provider->last_name) : '—',
            'base_price' => (float) $s->base_price,
            'duration_minutes' => $s->duration_minutes,
            'is_active' => (bool) $s->is_active,
            'is_featured' => (bool) $s->is_featured,
            'created_at' => $s->created_at?->toIso8601String(),
        ];
    }
}
