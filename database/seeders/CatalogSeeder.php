<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Nama kategori plastik packing sengaja dipisah kecil dan besar,
        // sama dengan nama produknya agar tidak tertukar saat input.
        $categories = [
            'Gas LPG', 'Deterjen', 'Plastik packing kecil', 'Plastik packing besar',
            'Botol parfum', 'Softener', 'Botol parfum ruangan', 'Pembersih lantai', 'Selotip',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['name' => $name]);
        }

        $suppliers = [
            ['name' => 'PT Sumber Gas Nusantara', 'contact_name' => 'Hendra', 'phone' => '021-7001111', 'address' => 'Jl. Industri Gas No. 4, Bekasi', 'lead_time_days' => 3, 'term_of_payment' => 14],
            ['name' => 'CV Aroma Kemas Nusantara', 'contact_name' => 'Lestari', 'phone' => '021-7002222', 'address' => 'Jl. Kemasan Raya No. 21, Tangerang', 'lead_time_days' => 7, 'term_of_payment' => 30],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['name' => $supplier['name']], $supplier);
        }

        $gas = Supplier::where('name', 'PT Sumber Gas Nusantara')->firstOrFail();
        $kemas = Supplier::where('name', 'CV Aroma Kemas Nusantara')->firstOrFail();

        // Sembilan barang fisik dari daftar barang awal. Tagihan (token listrik,
        // PDAM, IPAL, gaji) tidak dibuat sebagai produk karena masuk tabel expenses.
        $products = [
            ['sku' => 'LPG-12', 'name' => 'Gas LPG 12kg kondisi full', 'category' => 'Gas LPG', 'supplier_id' => $gas->id, 'unit' => 'pcs', 'min_stock' => 6, 'price_buy' => 21000],
            ['sku' => 'DTR-JRG', 'name' => 'Jerigen deterjen', 'category' => 'Deterjen', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 4, 'price_buy' => 33000],
            ['sku' => 'PLK-KCL', 'name' => 'Plastik packing kecil', 'category' => 'Plastik packing kecil', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 50, 'price_buy' => 500],
            ['sku' => 'PLK-BSR', 'name' => 'Plastik packing besar', 'category' => 'Plastik packing besar', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 30, 'price_buy' => 1200],
            ['sku' => 'BTL-PRF', 'name' => 'Botol parfum', 'category' => 'Botol parfum', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 20, 'price_buy' => 4200],
            ['sku' => 'BOX-SFT', 'name' => 'Box sachet softener', 'category' => 'Softener', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 10, 'price_buy' => 7800],
            ['sku' => 'BTL-PRR', 'name' => 'Botol parfum ruangan', 'category' => 'Botol parfum ruangan', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 12, 'price_buy' => 9500],
            ['sku' => 'PBR-LNT', 'name' => 'Pembersih lantai', 'category' => 'Pembersih lantai', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 10, 'price_buy' => 18000],
            ['sku' => 'SLT-01', 'name' => 'Selotip', 'category' => 'Selotip', 'supplier_id' => $kemas->id, 'unit' => 'pcs', 'min_stock' => 6, 'price_buy' => 5000],
        ];

        foreach ($products as $product) {
            $category = Category::where('name', $product['category'])->firstOrFail();

            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $category->id,
                    'supplier_id' => $product['supplier_id'],
                    'name' => $product['name'],
                    'unit' => $product['unit'],
                    'min_stock' => $product['min_stock'],
                    'price_buy' => $product['price_buy'],
                    'price_updated_at' => now('Asia/Jakarta')->toDateString(),
                    'is_active' => true,
                ],
            );
        }
    }
}
