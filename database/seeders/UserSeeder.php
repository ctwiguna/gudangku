<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();
        $cabang = Outlet::where('code', 'CKR-02')->firstOrFail();

        $users = [
            [
                'email' => 'owner@gudangku.test',
                'name' => 'Chandra TW',
                'outlet_id' => null,
                'role' => UserRole::Owner,
                'is_active' => true,
            ],
            [
                'email' => 'manajer.pusat@gudangku.test',
                'name' => 'Rina Manajer Pusat',
                'outlet_id' => $pusat->id,
                'role' => UserRole::Manajer,
                'is_active' => true,
            ],
            [
                'email' => 'manajer.cabang@gudangku.test',
                'name' => 'Budi Manajer Cabang',
                'outlet_id' => $cabang->id,
                'role' => UserRole::Manajer,
                'is_active' => true,
            ],
            [
                // Akun hasil register publik: belum aktif dan belum punya outlet,
                // jadi tidak bisa login sebelum owner mengaktifkan dan menugaskan outlet.
                'email' => 'manajer.baru@gudangku.test',
                'name' => 'Sari Pendaftar Baru',
                'outlet_id' => null,
                'role' => UserRole::Manajer,
                'is_active' => false,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->fill([
                'name' => $data['name'],
                'outlet_id' => $data['outlet_id'],
                'password' => 'password',
            ]);

            // role dan is_active tidak fillable, jadi diisi terkontrol di sini.
            $user->forceFill([
                'role' => $data['role'],
                'is_active' => $data['is_active'],
            ]);

            $user->save();
        }
    }
}
