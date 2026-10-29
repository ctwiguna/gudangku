<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props bersama. Hanya identitas dasar yang dikirim, tanpa data harga.
     * flash dipakai Toast untuk pesan sukses dan error.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            // Identitas aplikasi dipakai di halaman login, header, dan catatan kaki.
            'app' => [
                'name' => config('app.name'),
                'tagline' => config('app.tagline'),
            ],
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'is_owner' => $user->isOwner(),
                    'outlet' => $user->outlet === null ? null : [
                        'id' => $user->outlet->id,
                        'name' => $user->outlet->name,
                        'code' => $user->outlet->code,
                    ],
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
