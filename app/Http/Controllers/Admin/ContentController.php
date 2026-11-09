<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('content_items')->orderByDesc('updated_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'content_type' => ['required', Rule::in(['announcement', 'faq', 'banner'])],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $id = DB::table('content_items')->insertGetId([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => DB::table('content_items')->find($id)], 201);
    }

    public function update(Request $request, int $content): JsonResponse
    {
        $data = $request->validate([
            'content_type' => ['sometimes', Rule::in(['announcement', 'faq', 'banner'])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $affected = DB::table('content_items')->where('id', $content)->update([...$data, 'updated_at' => now()]);
        abort_unless($affected || DB::table('content_items')->where('id', $content)->exists(), 404);

        return response()->json(['data' => DB::table('content_items')->find($content)]);
    }

    public function destroy(int $content): JsonResponse
    {
        abort_unless(DB::table('content_items')->where('id', $content)->delete(), 404);

        return response()->json(['message' => 'Contenu supprimé.']);
    }
}
