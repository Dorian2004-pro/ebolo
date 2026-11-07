<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClientDataController extends Controller
{
    public function services(Request $request): JsonResponse
    {
        return $this->proxy($request, 'GET', '/services');
    }

    public function bookings(Request $request): JsonResponse
    {
        return $this->proxy($request, 'GET', '/bookings');
    }

    public function createBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artisan_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'location_type' => ['required', 'string', 'in:studio,salon,home'],
            'location_details' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->proxy($request, 'POST', '/bookings', $validated);
    }

    private function proxy(Request $request, string $method, string $path, array $payload = []): JsonResponse
    {
        $token = $request->session()->get('ebolo_token');

        abort_unless($token, 401, 'Veuillez vous connecter pour continuer.');

        try {
            $client = Http::acceptJson()
                ->withToken($token)
                ->timeout(10);

            $response = $method === 'POST'
                ? $client->asJson()->post($this->backendUrl($path), $payload)
                : $client->get($this->backendUrl($path));
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Le catalogue est momentanément indisponible. Réessayez dans quelques instants.',
            ], 503);
        }

        return response()->json($response->json(), $response->status());
    }

    private function backendUrl(string $path): string
    {
        return rtrim(config('services.ebolo.base_url'), '/').$path;
    }
}
