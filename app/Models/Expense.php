<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'user_id',
        'category',
        'expense_date',
        'period_start',
        'period_end',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'expense_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    /** Filter bulan dalam format Y-m, dipakai halaman pengeluaran. */
    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->whereYear('expense_date', substr($month, 0, 4))
            ->whereMonth('expense_date', substr($month, 5, 2));
    }

    public function scopeForCategory(Builder $query, ExpenseCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('expense_date')->orderByDesc('id');
    }
}
