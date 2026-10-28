<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'note',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'integer',
            'moved_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sumber movement: Purchase untuk in/out, StockOpname untuk adjustment. */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Saldo stok dari ledger. Dipakai sebelum transaksi yang mengurangi stok
     * untuk memastikan saldo tidak menjadi negatif.
     */
    public static function balance(int $outletId, int $productId): int
    {
        return (int) static::query()
            ->where('outlet_id', $outletId)
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeOfType(Builder $query, MovementType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('moved_at', [$from, $to]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('moved_at')->orderByDesc('id');
    }
}
