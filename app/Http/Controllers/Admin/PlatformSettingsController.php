<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformSettingsController extends Controller
{
    private const DEFAULTS = [
        'commission_percent' => '10',
        'cancellation_hours' => '24',
        'support_email' => 'support@ebolo.cm',
        'brand_name' => 'Ébolo',
    ];

    public function show(): JsonResponse
    {
        $saved = DB::table('platform_settings')->pluck('value', 'key')->all();

        return response()->json(['data' => array_merge(self::DEFAULTS, $saved)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'commission_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'cancellation_hours' => ['sometimes', 'integer', 'between:0,720'],
            'support_email' => ['sometimes', 'email', 'max:255'],
            'brand_name' => ['sometimes', 'string', 'max:120'],
        ]);

        foreach ($data as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        return $this->show();
    }
}
