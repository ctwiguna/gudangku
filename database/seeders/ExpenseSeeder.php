<?php

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'owner@gudangku.test')->firstOrFail();
        $pusat = Outlet::where('code', 'CKR-01')->firstOrFail();
        $cabang = Outlet::where('code', 'CKR-02')->firstOrFail();

        // Tanggal dibuat relatif terhadap bulan berjalan supaya kartu
        // "pengeluaran bulan ini" di dashboard selalu berisi.
        $monthStart = Carbon::now('Asia/Jakarta')->startOfMonth();

        $expenses = [
            // Outlet pusat
            ['outlet' => $pusat, 'category' => ExpenseCategory::Sewa, 'day' => 1, 'amount' => 3000000, 'note' => 'Sewa tempat bulan berjalan'],
            ['outlet' => $pusat, 'category' => ExpenseCategory::Listrik, 'day' => 5, 'amount' => 350000, 'note' => 'Token listrik outlet pusat'],
            ['outlet' => $pusat, 'category' => ExpenseCategory::Air, 'day' => 6, 'amount' => 180000, 'note' => 'Tagihan PDAM'],
            ['outlet' => $pusat, 'category' => ExpenseCategory::Ipal, 'day' => 7, 'amount' => 250000, 'note' => 'Iuran IPAL bulanan'],
            ['outlet' => $pusat, 'category' => ExpenseCategory::Lain, 'day' => 10, 'amount' => 120000, 'note' => 'Perbaikan keran dan lampu'],
            [
                'outlet' => $pusat, 'category' => ExpenseCategory::Gaji, 'day' => 19, 'amount' => 1500000,
                'note' => 'Gaji karyawan minggu ketiga', 'period' => [19, 25],
            ],
            // Outlet cabang
            ['outlet' => $cabang, 'category' => ExpenseCategory::Sewa, 'day' => 1, 'amount' => 2250000, 'note' => 'Sewa tempat cabang'],
            ['outlet' => $cabang, 'category' => ExpenseCategory::Listrik, 'day' => 5, 'amount' => 275000, 'note' => 'Token listrik cabang'],
            ['outlet' => $cabang, 'category' => ExpenseCategory::Air, 'day' => 6, 'amount' => 150000, 'note' => 'Tagihan PDAM cabang'],
            ['outlet' => $cabang, 'category' => ExpenseCategory::Ipal, 'day' => 7, 'amount' => 200000, 'note' => 'Iuran IPAL cabang'],
            [
                'outlet' => $cabang, 'category' => ExpenseCategory::Gaji, 'day' => 19, 'amount' => 1400000,
                'note' => 'Gaji karyawan minggu ketiga', 'period' => [19, 25],
            ],
        ];

        foreach ($expenses as $data) {
            $date = $monthStart->copy()->addDays($data['day'] - 1);
            $period = $data['period'] ?? null;

            Expense::updateOrCreate(
                [
                    'outlet_id' => $data['outlet']->id,
                    'category' => $data['category'],
                    'expense_date' => $date->toDateString(),
                    'amount' => $data['amount'],
                ],
                [
                    'user_id' => $owner->id,
                    'period_start' => $period ? $monthStart->copy()->addDays($period[0] - 1)->toDateString() : null,
                    'period_end' => $period ? $monthStart->copy()->addDays($period[1] - 1)->toDateString() : null,
                    'note' => $data['note'],
                ],
            );
        }
    }
}
