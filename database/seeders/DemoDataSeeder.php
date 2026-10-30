<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Data contoh lengkap untuk demo dan test otomatis:
     * kategori, supplier, produk, pembelian, pengeluaran, dan stock opname.
     *
     * Ikut memanggil seeder dasar supaya test selalu punya outlet, akun,
     * dan karyawan sebelum data transaksi dibuat.
     */
    public function run(): void
    {
        $this->call([
            OutletSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            CatalogSeeder::class,
            PurchaseSeeder::class,
            ExpenseSeeder::class,
            StockOpnameSeeder::class,
        ]);
    }
}
