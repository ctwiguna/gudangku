<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Menolak akun yang sudah dinonaktifkan pada setiap request terautentikasi.
     *
     * Login kredensial sudah memeriksa is_active, tetapi sesi yang dipulihkan
     * dari cookie remember-me tidak melewati controller login. Middleware ini
     * menutup jalur tersebut sekaligus menjadi lapis kedua bila pencabutan
     * baris sessions gagal atau tidak berjalan (temuan audit 31 Okt 2026).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Akun Anda dinonaktifkan. Hubungi owner.');
        }

        return $next($request);
    }
}
