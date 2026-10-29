<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        $outlets = [
            [
                'code' => 'KDM-01',
                'name' => 'Kedungmundu',
                'address' => 'Jl. Kedungmundu Raya, Semarang',
                'phone' => '024-767001',
                'is_active' => true,
            ],
            [
                'code' => 'TMB-01',
                'name' => 'Tembalang',
                'address' => 'Jl. Setiabudi, Tembalang, Semarang',
                'phone' => '024-767002',
                'is_active' => true,
            ],
            [
                'code' => 'ALT-01',
                'name' => 'Alton',
                'address' => 'Kawasan Alton, Semarang',
                'phone' => '024-767003',
                'is_active' => true,
            ],
        ];

        foreach ($outlets as $outlet) {
            Outlet::updateOrCreate(['code' => $outlet['code']], $outlet);
        }
    }
}
