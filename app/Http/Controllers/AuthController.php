<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($request->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $user = $request->user();

        // Akun nonaktif tidak boleh memakai aplikasi. Sesi dibatalkan lagi
        // supaya percobaan login ini tidak meninggalkan jejak sesi.
        if (! $user->is_active) {
            $this->terminateSession($request);

            throw ValidationException::withMessages([
                'email' => 'Akun belum aktif. Hubungi owner untuk aktivasi akun.',
            ]);
        }

        // Manajer wajib punya outlet. Owner boleh tanpa outlet.
        if ($user->isManajer() && $user->outlet_id === null) {
            $this->terminateSession($request);

            throw ValidationException::withMessages([
                'email' => 'Akun belum ditugaskan ke outlet. Hubungi owner.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Register publik selalu menghasilkan manajer nonaktif tanpa outlet.
        // Pembuatan owner hanya lewat seeder atau owner yang sudah ada.
        $user->forceFill([
            'role' => UserRole::Manajer,
            'is_active' => false,
            'outlet_id' => null,
        ])->save();

        return redirect()
            ->route('login')
            ->with('success', 'Pendaftaran berhasil. Akun menunggu aktivasi owner sebelum bisa dipakai.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda sudah keluar.');
    }

    private function terminateSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
