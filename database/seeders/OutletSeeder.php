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
                'code' => 'CKR-01',
                'name' => 'Outlet Cakrawala Pusat',
                'address' => 'Jl. Merdeka No. 10, Jakarta Pusat',
                'phone' => '021-5550101',
                'is_active' => true,
            ],
            [
                'code' => 'CKR-02',
                'name' => 'Outlet Cakrawala Cabang',
                'address' => 'Jl. Raya Bogor No. 88, Jakarta Timur',
                'phone' => '021-5550202',
                'is_active' => true,
            ],
        ];

        foreach ($outlets as $outlet) {
            Outlet::updateOrCreate(['code' => $outlet['code']], $outlet);
        }
    }
}
