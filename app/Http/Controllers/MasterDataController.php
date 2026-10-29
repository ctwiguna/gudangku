<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    /**
     * Halaman kategori produk.
     *
     * Supplier sudah dipindah ke halaman sendiri (Database Supplier) supaya
     * tidak ada dua tempat mengelola data yang sama. Halaman ini sekarang
     * hanya berisi kategori.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('MasterData/Index', [
            'openCreate' => $request->boolean('new'),
            'categories' => Category::withCount('products')
                ->ordered()
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products_count' => (int) $category->products_count,
                ]),
        ]);
    }
}
