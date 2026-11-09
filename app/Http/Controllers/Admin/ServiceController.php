<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use PaginatesAdminData;

    public function index(Request $request): JsonResponse
    {
        $query = Service::with('artisan.user');
        $this->search($query, ['name', 'description', 'category'], $request->query('search'));

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->query('active') !== null && $request->query('active') !== '') {
            $query->where('is_active', (bool) $request->integer('active'));
        }

        $categories = Service::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn (string $category) => ['id' => $category, 'name' => $category])
            ->values();

        return response()->json($this->paginate(
            $request,
            $query->latest(),
            fn (Service $service) => $this->present($service),
            ['categories' => $categories],
        ));
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        $service->update($data);

        return response()->json(['data' => $this->present($service->load('artisan.user'))]);
    }

    private function present(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category,
            'short_description' => $service->description,
            'provider' => $service->artisan?->user?->name ?? '—',
            'base_price' => (float) $service->price,
            'duration_minutes' => $service->duration_minutes,
            'is_featured' => (bool) $service->is_featured,
            'is_active' => (bool) $service->is_active,
        ];
    }
}
