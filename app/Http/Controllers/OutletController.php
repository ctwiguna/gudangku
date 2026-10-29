<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutletRequest;
use App\Http\Requests\UpdateOutletRequest;
use App\Models\Outlet;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutletController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Outlet::class);

        $outlets = Outlet::query()
            ->withCount(['users', 'purchases', 'stockMovements', 'stockOpnames', 'expenses'])
            ->ordered()
            ->get()
            ->map(fn (Outlet $outlet) => [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'code' => $outlet->code,
                'address' => $outlet->address,
                'phone' => $outlet->phone,
                'is_active' => $outlet->is_active,
                'users_count' => (int) $outlet->users_count,
                'purchases_count' => (int) $outlet->purchases_count,
                'movements_count' => (int) $outlet->stock_movements_count,
                'opnames_count' => (int) $outlet->stock_opnames_count,
                'expenses_count' => (int) $outlet->expenses_count,
            ]);

        return Inertia::render('Outlets/Index', [
            'outlets' => $outlets,
            // ?new=1 dipakai tombol "Tambah outlet" dari menu Gudang.
            'openCreate' => $request->boolean('new'),
        ]);
    }

    public function store(StoreOutletRequest $request): RedirectResponse
    {
        $outlet = Outlet::create($request->validated());

        return back()->with('success', "Outlet {$outlet->name} tersimpan.");
    }

    public function update(UpdateOutletRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->authorize('update', $outlet);

        $outlet->update($request->validated());

        return back()->with('success', "Perubahan outlet {$outlet->name} tersimpan.");
    }

    /**
     * Outlet nonaktif tidak muncul di form transaksi baru, tetapi data lama
     * tetap bisa dilihat. Ini jalur yang disarankan sebelum menghapus.
     */
    public function toggleStatus(Outlet $outlet): RedirectResponse
    {
        $this->authorize('update', $outlet);

        $outlet->update(['is_active' => ! $outlet->is_active]);

        $state = $outlet->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Outlet {$outlet->name} {$state}.");
    }

    /**
     * Outlet yang sudah punya data tidak boleh dihapus karena foreign key
     * memakai RESTRICT. Pesannya menyebut data apa yang menahan dan
     * menyarankan menonaktifkan outlet.
     */
    public function destroy(Outlet $outlet): RedirectResponse
    {
        $this->authorize('delete', $outlet);

        $counts = [
            'user' => $outlet->users()->count(),
            'pembelian' => $outlet->purchases()->count(),
            'pergerakan stok' => $outlet->stockMovements()->count(),
            'opname' => $outlet->stockOpnames()->count(),
            'pengeluaran' => $outlet->expenses()->count(),
        ];

        $blocking = array_filter($counts, fn (int $count) => $count > 0);

        if ($blocking !== []) {
            $detail = collect($blocking)
                ->map(fn (int $count, string $label) => "{$count} {$label}")
                ->implode(', ');

            return back()->with(
                'error',
                "Outlet {$outlet->name} masih punya data ({$detail}), jadi tidak bisa dihapus. "
                .'Nonaktifkan outlet ini supaya tidak muncul di form transaksi baru.',
            );
        }

        try {
            $name = $outlet->name;
            $outlet->delete();
        } catch (QueryException) {
            return back()->with(
                'error',
                "Outlet {$outlet->name} masih terhubung dengan data lain sehingga belum bisa dihapus.",
            );
        }

        return back()->with('success', "Outlet {$name} dihapus.");
    }
}
