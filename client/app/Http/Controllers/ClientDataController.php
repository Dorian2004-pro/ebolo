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

    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer'],
            'provider' => ['required', 'in:momo,orange'],
        ]);
        $token = $request->session()->get('ebolo_token');
        abort_unless($token, 401, 'Veuillez vous connecter pour continuer.');

        try {
            $client = Http::acceptJson()->withToken($token)->timeout(10);
            $payment = $client->asJson()->post($this->backendUrl('/payments/initiate'), $validated);
            if ($payment->failed()) {
                return response()->json($payment->json(), $payment->status());
            }

            $result = $client->asJson()->post($this->backendUrl('/payments/confirm'), [
                'payment_id' => $payment->json('id'),
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Le paiement de démonstration est indisponible. Réessayez.',
            ], 503);
        }

        return response()->json($result->json(), $result->status());
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->proxy($request, 'GET', '/me');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        return $this->proxy($request, 'PATCH', '/profile', $validated);
    }

    public function availability(Request $request, int $artisanId): JsonResponse
    {
        return $this->proxy($request, 'GET', '/artisans/'.$artisanId.'/availability');
    }

    public function messages(Request $request, int $userId): JsonResponse
    {
        return $this->proxy($request, 'GET', '/chat/'.$userId.'/messages');
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        return $this->proxy($request, 'POST', '/chat/send', $validated);
    }

    public function review(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artisan_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->proxy($request, 'POST', '/reviews', $validated);
    }

    private function proxy(Request $request, string $method, string $path, array $payload = []): JsonResponse
    {
        $token = $request->session()->get('ebolo_token');

        abort_unless($token, 401, 'Veuillez vous connecter pour continuer.');

        try {
            $client = Http::acceptJson()
                ->withToken($token)
                ->timeout(10);

            $response = match ($method) {
                'POST' => $client->asJson()->post($this->backendUrl($path), $payload),
                'PATCH' => $client->asJson()->patch($this->backendUrl($path), $payload),
                default => $client->get($this->backendUrl($path)),
            };
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
