<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return $this->backToTab('Kategori baru tersimpan.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return $this->backToTab('Perubahan kategori tersimpan.');
    }

    /**
     * Kategori yang masih dipakai produk tidak bisa dihapus karena foreign key
     * memakai RESTRICT. Pesan dijelaskan langkah perbaikannya, bukan error SQL.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $usedBy = $category->products()->count();

        if ($usedBy > 0) {
            return $this->backToTab(
                "Kategori {$category->name} masih dipakai {$usedBy} produk. "
                .'Pindahkan produk itu ke kategori lain lebih dulu.',
                'error',
            );
        }

        try {
            $name = $category->name;
            $category->delete();
        } catch (QueryException) {
            return $this->backToTab(
                "Kategori {$category->name} masih terhubung dengan data lain sehingga belum bisa dihapus.",
                'error',
            );
        }

        return $this->backToTab("Kategori {$name} dihapus.");
    }

    private function backToTab(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()
            ->route('master-data.index', ['tab' => 'kategori'])
            ->with($type, $message);
    }
}
