<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'supplier_id',
        'user_id',
        'invoice_no',
        'received_at',
        'priced_at',
        'paid_at',
        'total',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'priced_at' => 'date',
            'paid_at' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /** Sudah dihargai owner, karenanya masuk hitungan hutang. */
    public function isPriced(): bool
    {
        return $this->priced_at !== null;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /** Hutang supplier: sudah berharga dan belum dibayar. */
    public function isDebt(): bool
    {
        return $this->isPriced() && ! $this->isPaid();
    }

    /** Semua item sudah punya harga dan minimal ada satu item. */
    public function hasAllPrices(): bool
    {
        return $this->items()->exists()
            && $this->items()->whereNull('price')->doesntExist();
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    /** Antrean pembelian yang menunggu harga owner. */
    public function scopeUnpriced(Builder $query): Builder
    {
        return $query->whereNull('priced_at');
    }

    public function scopePriced(Builder $query): Builder
    {
        return $query->whereNotNull('priced_at');
    }

    /** Hutang yang masih berjalan. */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNotNull('priced_at')->whereNull('paid_at');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereNotNull('paid_at');
    }

    public function scopeLatestReceived(Builder $query): Builder
    {
        return $query->orderByDesc('received_at')->orderByDesc('id');
    }
}
