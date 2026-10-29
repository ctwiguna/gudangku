<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    /**
     * Outlet pusat sudah opname minggu ini, outlet cabang sengaja dibiarkan
     * belum supaya banner pengingat dan status kepatuhan punya isi nyata.
     */
    public function run(): void
    {
        $pusat = Outlet::where('code', 'KDM-01')->firstOrFail();
        $manajer = User::where('email', 'ina@gudangku.test')->firstOrFail();

        [$weekStart, $weekEnd] = StockOpname::currentWeek();

        // Opname tidak boleh dibuat ulang: kalau dihitung ulang dari ledger yang
        // sudah disesuaikan, selisihnya akan bergeser setiap kali seeder dijalankan.
        $existing = StockOpname::forOutlet($pusat->id)
            ->forWeek($weekStart->toDateString())
            ->first();

        if ($existing !== null && $existing->items()->exists()) {
            return;
        }

        $opname = StockOpname::updateOrCreate(
            [
                'outlet_id' => $pusat->id,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'user_id' => $manajer->id,
                'week_end' => $weekEnd->toDateString(),
                'completed_at' => $weekStart->copy()->addDays(2)->setTime(16, 30),
                'note' => 'Opname rutin mingguan outlet pusat.',
            ],
        );

        // Selisih contoh: plastik packing kecil kurang 4, selotip lebih 2.
        $differences = ['PLK-KCL' => -4, 'SLT-01' => 2];

        foreach (Product::active()->ordered()->get() as $product) {
            $systemQty = $product->stockIn($pusat->id);
            $difference = $differences[$product->sku] ?? 0;

            // Selisih positif tidak boleh membuat perhitungan stok jadi negatif.
            if ($systemQty + $difference < 0) {
                $difference = -$systemQty;
            }

            $item = $opname->items()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'system_qty' => $systemQty,
                    'counted_qty' => $systemQty + $difference,
                    'difference' => $difference,
                    'note' => $difference === 0 ? null : 'Selisih hasil hitung fisik',
                ],
            );

            if (! $item->hasDifference()) {
                continue;
            }

            // Selisih nol tidak menulis movement. Selisih tidak nol menyesuaikan
            // stok lewat satu baris adjustment.
            StockMovement::firstOrCreate(
                [
                    'reference_type' => StockOpname::class,
                    'reference_id' => $opname->id,
                    'product_id' => $product->id,
                    'type' => MovementType::Adjustment,
                ],
                [
                    'outlet_id' => $pusat->id,
                    'user_id' => $manajer->id,
                    'quantity' => $difference,
                    'note' => 'Penyesuaian stock opname '.$weekStart->toDateString(),
                    'moved_at' => $item->updated_at ?? now(),
                ],
            );
        }
    }
}
