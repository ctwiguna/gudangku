<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockService
{
    /**
     * Satu-satunya pintu penulisan ledger stok.
     *
     * Setiap perubahan stok wajib lewat sini supaya:
     * 1. Selalu ada satu baris stock_movements.
     * 2. Saldo tidak pernah menjadi negatif.
     * 3. Movement bertipe out selalu diperiksa terhadap saldo berjalan.
     *
     * Pemanggil bertanggung jawab membungkusnya dalam DB::transaction bersama
     * perubahan data lain, supaya stok dan dokumen tidak pernah berbeda.
     */
    public function record(
        Model $reference,
        int $outletId,
        int $productId,
        int $userId,
        MovementType $type,
        int $quantity,
        ?string $note = null,
        ?Carbon $movedAt = null,
    ): StockMovement {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Movement dengan quantity nol tidak boleh ditulis.');
        }

        if ($quantity < 0) {
            $this->assertSufficientStock($outletId, $productId, $quantity);
        }

        return StockMovement::create([
            'outlet_id' => $outletId,
            'product_id' => $productId,
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'note' => $note,
            'moved_at' => $movedAt ?? Carbon::now('Asia/Jakarta'),
        ]);
    }

    public function balance(int $outletId, int $productId): int
    {
        return StockMovement::balance($outletId, $productId);
    }

    /**
     * Saldo stok tidak boleh negatif. Pesannya menyebut saldo berjalan dan
     * jumlah yang diminta supaya pengguna tahu harus berbuat apa.
     */
    public function assertSufficientStock(int $outletId, int $productId, int $delta): void
    {
        $balance = $this->balance($outletId, $productId);

        if ($balance + $delta < 0) {
            throw ValidationException::withMessages([
                'items' => "Stok tidak cukup. Saldo saat ini {$balance}, permintaan ".abs($delta).'.',
            ]);
        }
    }
}
