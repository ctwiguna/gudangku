<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManager
{
    /**
     * Route yang memang hanya relevan untuk manajer outlet.
     * Otorisasi tingkat data tetap dilakukan policy dan scoping outlet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isManajer()) {
            abort(403, 'Halaman ini hanya untuk manajer outlet.');
        }

        return $next($request);
    }
}
