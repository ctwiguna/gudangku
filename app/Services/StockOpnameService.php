<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Menyimpan satu stock opname mingguan untuk satu outlet.
     *
     * Aturan penting:
     * 1. Stok sistem dihitung ulang di server saat submit, bukan memakai angka
     *    yang ditampilkan di form.
     * 2. Selisih nol tidak menulis movement. Selisih tidak nol menulis satu
     *    movement adjustment.
     * 3. Satu outlet hanya boleh punya satu opname per minggu.
     * 4. Semua penulisan terjadi dalam satu transaksi.
     *
     * @param  array<int, array{product_id: int|string, counted_qty: int|string, note?: string|null}>  $counts
     */
    public function submit(int $outletId, User $user, array $counts, ?string $note = null): StockOpname
    {
        [$weekStart, $weekEnd] = StockOpname::currentWeek();
        $weekStartDate = $weekStart->toDateString();

        if (StockOpname::forOutlet($outletId)->forWeek($weekStartDate)->exists()) {
            throw ValidationException::withMessages([
                'counts' => 'Outlet ini sudah melakukan stock opname minggu ini. '
                    .'Satu outlet hanya boleh satu opname per minggu.',
            ]);
        }

        return DB::transaction(function () use ($outletId, $user, $counts, $note, $weekStart, $weekEnd, $weekStartDate): StockOpname {
            $opname = StockOpname::create([
                'outlet_id' => $outletId,
                'user_id' => $user->id,
                'week_start' => $weekStartDate,
                'week_end' => $weekEnd->toDateString(),
                'completed_at' => Carbon::now('Asia/Jakarta'),
                'note' => $note,
            ]);

            foreach ($counts as $row) {
                $productId = (int) $row['product_id'];
                $countedQty = (int) $row['counted_qty'];

                // Dihitung ulang dari ledger, bukan dari nilai yang dikirim klien.
                $systemQty = $this->stock->balance($outletId, $productId);
                $difference = $countedQty - $systemQty;

                $opname->items()->create([
                    'product_id' => $productId,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'difference' => $difference,
                    'note' => $row['note'] ?? null,
                ]);

                if ($difference === 0) {
                    continue;
                }

                $this->stock->record(
                    reference: $opname,
                    outletId: $outletId,
                    productId: $productId,
                    userId: $user->id,
                    type: MovementType::Adjustment,
                    quantity: $difference,
                    note: 'Penyesuaian stock opname '.$weekStartDate,
                    movedAt: Carbon::now('Asia/Jakarta'),
                );
            }

            return $opname;
        });
    }
}
