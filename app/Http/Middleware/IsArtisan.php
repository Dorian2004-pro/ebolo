<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsArtisan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && in_array($user->role?->slug, ['artisan', 'salon'], true) && $user->status === 'active',
            403,
        );

        return $next($request);
    }
}
