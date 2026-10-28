<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Route redirect, bukan closure, supaya `route:cache` tetap jalan di produksi.
Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// Data keuangan hanya untuk owner. Manajer yang membuka URL langsung mendapat 403.
Route::middleware(['auth', 'owner'])->group(function (): void {
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Kategori dan supplier dalam satu halaman bertab.
    Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

    Route::post('master-data/kategori', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('master-data/kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('master-data/kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('master-data/supplier', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('master-data/supplier/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('master-data/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
});
