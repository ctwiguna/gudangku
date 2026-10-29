<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Database supplier: informasi lengkap pemasok agar mudah dihubungi
     * dan dibandingkan (kontak, alamat, lead time, term of payment).
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isOwner(), 403, 'Database supplier hanya untuk owner.');

        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::withCount(['products', 'purchases'])
                ->ordered()
                ->get()
                ->map(fn (Supplier $supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contact_name' => $supplier->contact_name,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'lead_time_days' => $supplier->lead_time_days,
                    'term_of_payment' => $supplier->term_of_payment,
                    'products_count' => (int) $supplier->products_count,
                    'purchases_count' => (int) $supplier->purchases_count,
                ]),
            'openCreate' => $request->boolean('new'),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return $this->backToSupplierList('Supplier baru tersimpan.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return $this->backToSupplierList('Perubahan supplier tersimpan.');
    }

    /**
     * Produk yang memakai supplier ini hanya kehilangan relasi (SET NULL),
     * tetapi riwayat pembelian memakai RESTRICT sehingga supplier yang sudah
     * pernah dipakai bertransaksi tidak boleh dihapus.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $purchases = $supplier->purchases()->count();

        if ($purchases > 0) {
            return $this->backToSupplierList(
                "Supplier {$supplier->name} dipakai di {$purchases} pembelian, jadi riwayatnya tidak boleh hilang. "
                .'Biarkan supplier ini tetap ada.',
                'error',
            );
        }

        try {
            $name = $supplier->name;
            $supplier->delete();
        } catch (QueryException) {
            return $this->backToSupplierList(
                "Supplier {$supplier->name} masih terhubung dengan data lain sehingga belum bisa dihapus.",
                'error',
            );
        }

        return $this->backToSupplierList("Supplier {$name} dihapus.");
    }

    private function backToSupplierList(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()->route('suppliers.index')->with($type, $message);
    }
}
