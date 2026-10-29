<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Menu Gudang untuk owner: pintu masuk master data dan transaksi barang.
     * Di sinilah aksi "tambah" dikumpulkan supaya tidak perlu dicari di
     * dalam tab masing-masing halaman.
     */
    public function index(): Response
    {
        abort_unless(request()->user()?->isOwner(), 403, 'Menu Gudang hanya untuk owner.');

        return Inertia::render('Warehouse/Index', [
            'counts' => [
                'products' => Product::count(),
                'products_active' => Product::where('is_active', true)->count(),
                'categories' => Category::count(),
                'suppliers' => Supplier::count(),
                'outlets' => Outlet::count(),
                'unpriced' => Purchase::whereNull('priced_at')->count(),
            ],
        ]);
    }
}
