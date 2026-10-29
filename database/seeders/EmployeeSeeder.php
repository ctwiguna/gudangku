<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Data acuan gaji. Pengeluaran gaji per outlet tetap dicatat
        // di tabel expenses kategori gaji.
        $employees = [
            ['name' => 'Siti Aminah', 'salary' => 120000, 'salary_unit' => 'harian', 'note' => 'bagian cuci'],
            ['name' => 'Budi Santoso', 'salary' => 1500000, 'salary_unit' => 'bulanan', 'note' => 'kasir'],
            ['name' => 'Rina Marlina', 'salary' => 350000, 'salary_unit' => 'mingguan', 'note' => 'setrika'],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['name' => $employee['name']],
                [...$employee, 'is_active' => true],
            );
        }
    }
}
