<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function admin(Request $request): Admin
    {
        return $request->attributes->get('admin');
    }

    /** Refuse (403) si l'administrateur connecté n'a pas la permission. Le super_admin les a toutes. */
    protected function requirePermission(Request $request, string $permission): void
    {
        abort_unless($this->admin($request)->can($permission), 403, "Permission requise : {$permission}");
    }

    /** Recherche insensible à la casse sur plusieurs colonnes (compatible SQLite / MySQL / PostgreSQL). */
    protected function search(EloquentBuilder|QueryBuilder $query, array $columns, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }
        $like = '%'.mb_strtolower(addcslashes($term, '\\%_')).'%';

        $query->where(function ($q) use ($columns, $like) {
            foreach ($columns as $column) {
                $q->orWhereRaw("LOWER({$column}) LIKE ?", [$like]);
            }
        });
    }

    protected function paginated($paginator, callable $map): array
    {
        return [
            'data' => $paginator->getCollection()->map($map)->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    protected function perPage(Request $request, int $default = 15): int
    {
        return max(5, min(100, (int) $request->integer('per_page', $default)));
    }
}
