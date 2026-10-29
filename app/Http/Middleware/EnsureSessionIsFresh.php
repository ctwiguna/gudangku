<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsFresh
{
    /**
     * Batas waktu maksimum sejak login (absolute timeout).
     *
     * Idle timeout saja tidak cukup: karena sifatnya sliding, sesi bisa hidup
     * berhari-hari selama pengguna aktif. Middleware ini memaksa login ulang
     * setelah jangka waktu tertentu, berapa pun aktivitasnya.
     *
     * Waktu diambil dari server, bukan dari nilai yang bisa diubah klien.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $session = $request->session();
        $loginAt = $session->get('auth.login_at');
        $now = Carbon::now('Asia/Jakarta')->timestamp;

        // Sesi yang dibuat sebelum fitur ini aktif: tandai mulai dari sekarang
        // supaya pengguna tidak langsung terlempar keluar setelah deploy.
        if (! is_int($loginAt) && ! is_string($loginAt)) {
            $session->put('auth.login_at', $now);

            return $next($request);
        }

        $maxAgeSeconds = max(1, (int) config('session.absolute_lifetime', 720)) * 60;

        if ($now - (int) $loginAt <= $maxAgeSeconds) {
            return $next($request);
        }

        Auth::logout();
        $session->invalidate();
        $session->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', 'Sesi berakhir karena mencapai batas waktu maksimum. Silakan masuk lagi.');
    }
}
