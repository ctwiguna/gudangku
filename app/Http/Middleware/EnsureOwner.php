<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwner
{
    /**
     * Route grup data keuangan dan pengaturan hanya untuk owner.
     * Manajer yang membuka URL langsung mendapat 403, bukan halaman kosong.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isOwner()) {
            abort(403, 'Halaman ini hanya untuk owner.');
        }

        return $next($request);
    }
}
