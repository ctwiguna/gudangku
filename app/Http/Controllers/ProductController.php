<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Daftar produk dengan saldo stok dari ledger.
     *
     * Owner melihat semua outlet (atau satu outlet lewat filter) dan menerima
     * harga beli. Manajer hanya melihat stok outlet sendiri dan tidak menerima
     * field harga sama sekali.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user();
        $isOwner = $user->isOwner();

        $outletId = $isOwner
            ? ($request->integer('outlet') ?: null)
            : (int) $user->outlet_id;

        $query = Product::query()
            ->with(['category:id,name', 'supplier:id,name'])
            ->ordered();

        if ($outletId === null) {
            $query->withTotalStock();
        } else {
            $query->withStock($outletId);
        }

        $products = $query->get()->map(function (Product $product) use ($isOwner): array {
            $stock = (int) ($product->stock_qty ?? 0);

            $row = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'min_stock' => (int) $product->min_stock,
                'is_active' => $product->is_active,
                'category' => $product->category?->name,
                'supplier' => $product->supplier?->name,
                'stock' => $stock,
                'below_min' => $stock < (int) $product->min_stock,
            ];

            // Harga hanya dikirim ke owner. Manajer tidak menerima field ini.
            if ($isOwner) {
                $row['price_buy'] = (float) $product->price_buy;
            }

            return $row;
        });

        $outletLabel = 'Semua outlet';

        if ($outletId !== null) {
            $outletLabel = Outlet::whereKey($outletId)->value('name') ?? 'Outlet tidak ditemukan';
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'canManage' => $isOwner,
            'outletLabel' => $outletLabel,
            'filters' => ['outlet' => $outletId],
            'outlets' => $isOwner
                ? Outlet::ordered()->get(['id', 'name', 'code'])
                : [],
            'categories' => $isOwner ? Category::ordered()->get(['id', 'name']) : [],
            'suppliers' => $isOwner ? Supplier::ordered()->get(['id', 'name']) : [],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Form', [
            'product' => null,
            'categories' => Category::ordered()->get(['id', 'name']),
            'suppliers' => Supplier::ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', "Produk {$product->name} tersimpan.");
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Products/Form', [
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'supplier_id' => $product->supplier_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'min_stock' => (int) $product->min_stock,
                'price_buy' => (float) $product->price_buy,
                'is_active' => $product->is_active,
            ],
            'categories' => Category::ordered()->get(['id', 'name']),
            'suppliers' => Supplier::ordered()->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', "Perubahan produk {$product->name} tersimpan.");
    }

    /**
     * Produk yang sudah punya riwayat stok, item pembelian, atau item opname
     * tidak boleh dihapus. Saran perbaikannya adalah menonaktifkan produk.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $movements = $product->stockMovements()->count();
        $purchaseItems = $product->purchaseItems()->count();
        $opnameItems = $product->stockOpnameItems()->count();

        if ($movements + $purchaseItems + $opnameItems > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', "Produk {$product->name} sudah punya riwayat stok atau transaksi, "
                    .'jadi tidak bisa dihapus. Nonaktifkan produk ini supaya tidak muncul di form baru.');
        }

        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', "Produk {$name} dihapus.");
    }

    /** Produk nonaktif tidak muncul di form penerimaan dan opname. */
    public function toggleStatus(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['is_active' => ! $product->is_active]);

        $state = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('products.index')
            ->with('success', "Produk {$product->name} {$state}.");
    }
}
