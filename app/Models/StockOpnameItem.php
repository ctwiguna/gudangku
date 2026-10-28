<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockOpnameItemFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_qty',
        'counted_qty',
        'difference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'counted_qty' => 'integer',
            'difference' => 'integer',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Selisih nol tidak menulis movement penyesuaian. */
    public function hasDifference(): bool
    {
        return $this->difference !== 0;
    }
}
