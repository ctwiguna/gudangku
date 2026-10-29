<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly SessionService $sessions)
    {
    }
    /**
     * Profil sendiri untuk kedua role. Role, outlet, dan status aktif
     * ditampilkan sebagai informasi saja, tidak bisa diubah dari sini.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'role_label' => $user->role->label(),
                'outlet' => $user->outlet?->name,
                'outlet_code' => $user->outlet?->code,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        // Password baru berarti perangkat lain yang masih memakai password lama
        // harus langsung keluar. Sesi saat ini dipertahankan.
        if (! empty($validated['password'])) {
            $revoked = $this->sessions->revokeForUser(
                (int) $user->id,
                $request->session()->getId(),
            );

            return back()->with('success', $revoked > 0
                ? "Profil dan password diperbarui. {$revoked} sesi lain di perangkat lain dikeluarkan."
                : 'Profil dan password diperbarui.');
        }

        return back()->with('success', 'Profil diperbarui.');
    }
}
