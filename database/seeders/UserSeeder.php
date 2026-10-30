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
        $kedungmundu = Outlet::where('code', 'KDM-01')->firstOrFail();
        $tembalang = Outlet::where('code', 'TMB-01')->firstOrFail();
        $alton = Outlet::where('code', 'ALT-01')->firstOrFail();

        $users = [
            [
                'email' => 'owner@gudangku.test',
                'name' => 'Chandra TW',
                'outlet_id' => null,
                'role' => UserRole::Owner,
                'is_active' => true,
            ],
            [
                // Manajer sekaligus karyawan di outlet Kedungmundu.
                'email' => 'manajer1@gudangku.test',
                'name' => 'Ina',
                'outlet_id' => $kedungmundu->id,
                'role' => UserRole::Manajer,
                'is_active' => true,
            ],
            [
                'email' => 'manajer3@gudangku.test',
                'name' => 'Salsi',
                'outlet_id' => $tembalang->id,
                'role' => UserRole::Manajer,
                'is_active' => true,
            ],
            [
                'email' => 'manajer2@gudangku.test',
                'name' => 'Nafa',
                'outlet_id' => $alton->id,
                'role' => UserRole::Manajer,
                'is_active' => true,
            ],
            [
                // Akun hasil register publik: belum aktif dan belum punya outlet,
                // jadi tidak bisa login sebelum owner mengaktifkan dan menugaskan outlet.
                'email' => 'kandidat@gudangku.test',
                'name' => 'Kandidat Pendaftar',
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
                'password' => 'hidupberkah',
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
