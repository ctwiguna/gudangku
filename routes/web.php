<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
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

    // Owner melihat semua outlet, manajer hanya stok outlet sendiri dan tanpa harga.
    Route::get('products', [ProductController::class, 'index'])->name('products.index');

    // Penerimaan barang: owner dan manajer, manajer hanya outlet sendiri.
    Route::get('receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('receipts/{purchase}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('receipts/{purchase}/edit', [ReceiptController::class, 'edit'])->name('receipts.edit');
    Route::put('receipts/{purchase}', [ReceiptController::class, 'update'])->name('receipts.update');

    // Stock opname mingguan: manajer hanya outlet sendiri, owner boleh semua.
    Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
    Route::post('stock-opnames', [StockOpnameController::class, 'store'])->name('stock-opnames.store');
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

    // Produk hanya boleh dikelola owner, termasuk harga beli per pcs.
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::patch('products/{product}/status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

    // Harga dan pembayaran pembelian hanya untuk owner.
    Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/{purchase}/price', [PurchaseController::class, 'edit'])->name('purchases.price.edit');
    Route::put('purchases/{purchase}/price', [PurchaseController::class, 'update'])->name('purchases.price.update');
    Route::patch('purchases/{purchase}/paid', [PurchaseController::class, 'markPaid'])->name('purchases.paid');
    Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    // Pengaturan owner: outlet dan user.
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::get('outlets', [OutletController::class, 'index'])->name('outlets.index');
    Route::post('outlets', [OutletController::class, 'store'])->name('outlets.store');
    Route::put('outlets/{outlet}', [OutletController::class, 'update'])->name('outlets.update');
    Route::patch('outlets/{outlet}/status', [OutletController::class, 'toggleStatus'])->name('outlets.toggle-status');
    Route::delete('outlets/{outlet}', [OutletController::class, 'destroy'])->name('outlets.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
