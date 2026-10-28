<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class StockOpname extends Model
{
    /** @use HasFactory<\Database\Factories\StockOpnameFactory> */
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'user_id',
        'week_start',
        'week_end',
        'completed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'completed_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Batas minggu berjalan: Senin 00.00 sampai Minggu 23.59 WIB.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function currentWeek(): array
    {
        $start = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return [$start, $end];
    }

    public static function currentWeekStart(): string
    {
        return static::currentWeek()[0]->toDateString();
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    public function scopeForWeek(Builder $query, string $weekStart): Builder
    {
        return $query->whereDate('week_start', $weekStart);
    }

    public function scopeLatestWeeks(Builder $query): Builder
    {
        return $query->orderByDesc('week_start');
    }
}
