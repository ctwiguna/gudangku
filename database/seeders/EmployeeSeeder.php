<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Data acuan gaji. Pengeluaran gaji per outlet tetap dicatat
        // di tabel expenses kategori gaji.
        $pusat = Outlet::where('code', 'CKR-01')->first();
        $cabang = Outlet::where('code', 'CKR-02')->first();

        $employees = [
            ['outlet_id' => $pusat?->id, 'name' => 'Siti Aminah', 'salary' => 120000, 'salary_unit' => 'harian', 'note' => 'bagian cuci'],
            ['outlet_id' => $pusat?->id, 'name' => 'Budi Santoso', 'salary' => 1500000, 'salary_unit' => 'bulanan', 'note' => 'kasir'],
            ['outlet_id' => $cabang?->id, 'name' => 'Rina Marlina', 'salary' => 350000, 'salary_unit' => 'mingguan', 'note' => 'setrika'],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['name' => $employee['name']],
                [...$employee, 'is_active' => true],
            );
        }
    }
}
