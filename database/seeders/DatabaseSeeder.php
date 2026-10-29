<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: outlet dulu karena user terikat outlet,
     * lalu katalog, lalu transaksi yang memakai produk.
     * Semua seeder idempotent, jadi aman dijalankan berulang.
     */
    public function run(): void
    {
        $this->call([
            OutletSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            PurchaseSeeder::class,
            ExpenseSeeder::class,
            EmployeeSeeder::class,
            StockOpnameSeeder::class,
        ]);
    }
}
