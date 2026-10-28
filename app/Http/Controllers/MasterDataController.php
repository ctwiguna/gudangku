<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    /**
     * Kategori dan supplier dalam satu halaman bertab.
     * Data keduanya dikirim sekaligus supaya pindah tab tidak perlu request baru.
     */
    public function index(Request $request): Response
    {
        $tab = $request->string('tab')->toString() === 'supplier' ? 'supplier' : 'kategori';

        return Inertia::render('MasterData/Index', [
            'tab' => $tab,
            'categories' => Category::withCount('products')
                ->ordered()
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products_count' => (int) $category->products_count,
                ]),
            'suppliers' => Supplier::withCount(['products', 'purchases'])
                ->ordered()
                ->get()
                ->map(fn (Supplier $supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contact_name' => $supplier->contact_name,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                    'products_count' => (int) $supplier->products_count,
                    'purchases_count' => (int) $supplier->purchases_count,
                ]),
        ]);
    }
}
