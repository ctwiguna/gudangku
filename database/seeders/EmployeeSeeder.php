<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Karyawan per outlet. Gaji diset seragam Rp600.000 per minggu.
     * Beberapa nama (Ina, Salsi, Nafa) juga dipakai sebagai akun manajer outlet.
     */
    public function run(): void
    {
        $perOutlet = [
            'KDM-01' => ['Salsa', 'Ida', 'Fera', 'Mila', 'Cynthia', 'Sevina', 'Salwa', 'Ina'],
            'TMB-01' => ['Dinda', 'Salsi', 'Valen', 'Shifa'],
            'ALT-01' => ['Nafa', 'Ajwa'],
        ];

        foreach ($perOutlet as $code => $names) {
            $outlet = Outlet::where('code', $code)->first();

            foreach ($names as $name) {
                Employee::updateOrCreate(
                    ['outlet_id' => $outlet?->id, 'name' => $name],
                    [
                        'salary' => 600000,
                        'salary_unit' => 'mingguan',
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
