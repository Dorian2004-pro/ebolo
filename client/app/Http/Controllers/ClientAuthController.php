<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClientAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type' => ['sometimes', 'required', 'in:client,artisan,salon'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'same:password'],
            'accepted_terms' => ['required', 'accepted'],
            'interests' => ['sometimes', 'array', 'max:9'],
            'interests.*' => ['string', 'in:tresses,perruques,locks,maquillage,manucure,soins,couture,tailoring,retouches'],
        ]);

        $accountType = $validated['account_type'] ?? 'client';
        $roleId = config("services.ebolo.{$accountType}_role_id");
        if ($roleId < 1) {
            return response()->json([
                'message' => "Configurez l’identifiant du rôle {$accountType} dans les paramètres Ébolo.",
            ], 503);
        }
        $providerUrl = rtrim((string) config('services.ebolo.provider_url'), '/');
        if ($accountType !== 'client' && $providerUrl === '') {
            return response()->json([
                'message' => 'Configurez EBOLO_PROVIDER_APP_URL pour accéder à l’espace professionnel.',
            ], 503);
        }

        $payload = $validated;
        unset($payload['account_type'], $payload['accepted_terms']);
        $response = $this->sendToBackend('/register', [
            ...$payload,
            'role_id' => $roleId,
        ]);

        if ($response->failed()) {
            return $this->forwardError($response);
        }

        $data = $response->json();
        if ($accountType !== 'client') {
            $revocation = $this->backendRequest('POST', '/logout', $data['token']);
            if ($revocation->failed()) {
                return $this->forwardError($revocation);
            }

            return response()->json(['redirect' => $providerUrl.'/connexion'], 201);
        }

        return $this->startClientSession($request, $data['token'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $response = $this->sendToBackend('/login', $validated);

        if ($response->failed()) {
            return $this->forwardError($response);
        }

        $data = $response->json();

        return $this->startClientSession($request, $data['token']);
    }

    private function startClientSession(Request $request, string $token, int $status = 200): JsonResponse
    {
        $profile = $this->backendRequest('GET', '/me', $token);

        if ($profile->failed()) {
            $revocation = $this->backendRequest('POST', '/logout', $token);

            if ($revocation->failed()) {
                return $this->forwardError($revocation);
            }

            return $this->forwardError($profile);
        }

        $user = $profile->json('user');

        if (($user['role']['slug'] ?? null) !== 'client') {
            $revocation = $this->backendRequest('POST', '/logout', $token);

            if ($revocation->failed()) {
                return $this->forwardError($revocation);
            }

            return response()->json([
                'message' => 'Cet espace est réservé aux comptes clients.',
            ], 403);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'ebolo_token' => $token,
            'ebolo_user' => $user,
        ]);

        return response()->json(['redirect' => route('home')], $status);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->session()->get('ebolo_token');

        if ($token) {
            $response = $this->backendRequest('POST', '/logout', $token);

            if ($response->failed()) {
                return $this->forwardError($response);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('home')]);
    }

    private function sendToBackend(string $path, array $payload): Response
    {
        return $this->backendRequest('POST', $path, payload: $payload);
    }

    private function backendRequest(string $method, string $path, ?string $token = null, array $payload = []): Response
    {
        $client = Http::acceptJson()->timeout(10);

        if ($token) {
            $client = $client->withToken($token);
        }

        try {
            return $method === 'POST'
                ? $client->asJson()->post($this->backendUrl($path), $payload)
                : $client->get($this->backendUrl($path));
        } catch (ConnectionException $exception) {
            report($exception);

            return Http::response([
                'message' => 'Le service est momentanément indisponible. Réessayez dans quelques instants.',
            ], 503);
        }
    }

    private function backendUrl(string $path): string
    {
        return rtrim(config('services.ebolo.base_url'), '/').$path;
    }

    private function forwardError(Response $response): JsonResponse
    {
        return response()->json([
            'message' => $response->json('message', 'La demande n’a pas pu aboutir.'),
            'errors' => $response->json('errors', []),
        ], $response->status());
    }
}
