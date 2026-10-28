<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return $this->backToTab('Supplier baru tersimpan.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return $this->backToTab('Perubahan supplier tersimpan.');
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
            return $this->backToTab(
                "Supplier {$supplier->name} dipakai di {$purchases} pembelian, jadi riwayatnya tidak boleh hilang. "
                .'Biarkan supplier ini tetap ada.',
                'error',
            );
        }

        try {
            $name = $supplier->name;
            $supplier->delete();
        } catch (QueryException) {
            return $this->backToTab(
                "Supplier {$supplier->name} masih terhubung dengan data lain sehingga belum bisa dihapus.",
                'error',
            );
        }

        return $this->backToTab("Supplier {$name} dihapus.");
    }

    private function backToTab(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()
            ->route('master-data.index', ['tab' => 'supplier'])
            ->with($type, $message);
    }
}
