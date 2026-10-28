<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => User::with('outlet:id,name,code')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'outlet_id' => $user->outlet_id,
                    'outlet' => $user->outlet?->name,
                    'outlet_code' => $user->outlet?->code,
                    'is_active' => $user->is_active,
                ]),
            'outlets' => Outlet::ordered()->get(['id', 'name', 'code']),
            'roles' => array_map(
                fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()],
                UserRole::cases(),
            ),
            'summary' => [
                'total' => User::count(),
                'owners' => User::owners()->count(),
                'managers' => User::where('role', UserRole::Manajer)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * role dan is_active tidak fillable, jadi diisi lewat forceFill.
     * Manajer wajib punya outlet, owner selalu tanpa outlet.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'outlet_id' => $role->isOwner() ? null : (int) $validated['outlet_id'],
        ]);

        $user->forceFill([
            'role' => $role,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('success', "User {$user->name} tersimpan sebagai {$role->label()}.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();
        $role = UserRole::from($validated['role']);
        $active = $request->boolean('is_active');
        $actingId = $request->user()?->id;

        // Owner tidak bisa menonaktifkan atau menurunkan akunnya sendiri.
        if ($user->id === $actingId && ! $active) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->id === $actingId && ! $role->isOwner()) {
            return back()->with('error', 'Anda tidak bisa menurunkan role akun sendiri.');
        }

        // Sistem menolak kalau owner terakhir hilang.
        if ($user->isOwner() && (! $role->isOwner() || ! $active) && User::owners()->count() <= 1) {
            return back()->with('error', 'Owner terakhir tidak boleh diturunkan role atau dinonaktifkan.');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'outlet_id' => $role->isOwner() ? null : (int) $validated['outlet_id'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->forceFill([
            'role' => $role,
            'is_active' => $active,
        ])->save();

        return back()->with('success', "Perubahan user {$user->name} tersimpan.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->id === $request->user()?->id) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }

        if ($user->is_active && $user->isOwner() && User::owners()->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Owner aktif terakhir tidak boleh dinonaktifkan.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $state = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "User {$user->name} {$state}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()?->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        if ($user->isOwner() && User::owners()->count() <= 1) {
            return back()->with('error', 'Owner terakhir tidak boleh dihapus.');
        }

        try {
            $name = $user->name;
            $user->delete();
        } catch (QueryException) {
            return back()->with(
                'error',
                "User {$user->name} sudah punya riwayat transaksi, jadi tidak bisa dihapus. "
                .'Nonaktifkan akun ini supaya tidak bisa login.',
            );
        }

        return back()->with('success', "User {$name} dihapus.");
    }
}
