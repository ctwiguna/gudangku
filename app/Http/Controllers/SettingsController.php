<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Halaman pengaturan owner: pintu masuk ke outlet dan user.
     * Kategori, supplier, dan produk tetap berada di menu Gudang.
     */
    public function index(): Response
    {
        abort_unless(request()->user()?->isOwner(), 403, 'Halaman ini hanya untuk owner.');

        return Inertia::render('Settings/Index', [
            'counts' => [
                'outlets' => Outlet::count(),
                'outlets_active' => Outlet::where('is_active', true)->count(),
                'users' => User::count(),
                'users_inactive' => User::where('is_active', false)->count(),
                'categories' => Category::count(),
                'suppliers' => Supplier::count(),
                'products' => Product::count(),
            ],
        ]);
    }
}
