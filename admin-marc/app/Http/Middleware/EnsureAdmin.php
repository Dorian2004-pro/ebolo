<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Équivalent de src/middleware.ts + getAdminUser() du projet Next.js :
 * le jeton Sanctum doit appartenir à un utilisateur actif, non suspendu, dont la fiche admin est active.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $admin = $user ? Admin::where('user_id', $user->id)->first() : null;

        if (! $user || ! $user->is_active || $user->is_suspended || ! $admin || ! $admin->is_active) {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        // Disponible dans tous les contrôleurs : $request->attributes->get('admin')
        $request->attributes->set('admin', $admin);

        if (! $admin->last_activity_at || $admin->last_activity_at->diffInMinutes(now()) >= 5) {
            $admin->forceFill(['last_activity_at' => now()])->save();
        }

        return $next($request);
    }
}
