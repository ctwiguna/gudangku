<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data dasar aplikasi: outlet, akun pengguna, dan karyawan.
     *
     * Data contoh produk, supplier, pembelian, pengeluaran, dan opname
     * sengaja TIDAK ikut di sini supaya database bisa dimulai kosong dan
     * diisi lewat aplikasi. Untuk mengisi data contoh (dipakai test otomatis),
     * jalankan: php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        $this->call([
            OutletSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
