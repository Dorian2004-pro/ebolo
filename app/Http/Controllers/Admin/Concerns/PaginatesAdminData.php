<?php

namespace App\Http\Controllers\Admin\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait PaginatesAdminData
{
    protected function search(Builder $query, array $columns, mixed $term): void
    {
        if (! is_string($term) || trim($term) === '') {
            return;
        }

        $term = '%'.trim($term).'%';

        $query->where(function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $term);
            }
        });
    }

    protected function paginate(Request $request, Builder $query, Closure $present, array $extra = []): array
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page = $query->paginate($perPage)->through($present);

        return [
            'data' => $page->items(),
            'meta' => [
                'page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ] + $extra;
    }
}
