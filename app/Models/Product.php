<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'unit',
        'min_stock',
        'price_buy',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'integer',
            'price_buy' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /** Produk nonaktif tidak muncul di form penerimaan dan opname. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Saldo stok satu produk di satu outlet adalah jumlah quantity pada ledger.
     * Tidak ada kolom saldo di tabel produk, jadi tidak ada dua sumber kebenaran.
     */
    public function stockIn(int $outletId): int
    {
        return (int) $this->stockMovements()
            ->where('outlet_id', $outletId)
            ->sum('quantity');
    }

    /** Melampirkan saldo stok outlet tertentu sebagai kolom `stock_qty`. */
    public function scopeWithStock(Builder $query, int $outletId): Builder
    {
        return $query->withSum(
            ['stockMovements as stock_qty' => fn (Builder $movement) => $movement->where('outlet_id', $outletId)],
            'quantity',
        );
    }

    /**
     * Produk dengan saldo di bawah min_stock pada outlet tertentu.
     * Nilai outlet diambil dari binding parameter, bukan dirangkai ke SQL.
     */
    public function scopeBelowMinStock(Builder $query, int $outletId): Builder
    {
        return $query->withStock($outletId)
            ->havingRaw('coalesce(stock_qty, 0) < products.min_stock');
    }
}
