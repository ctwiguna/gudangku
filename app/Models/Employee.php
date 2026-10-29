<?php

namespace App\Models;

use App\Enums\SalaryUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'name',
        'salary',
        'salary_unit',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'salary_unit' => SalaryUnit::class,
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /** Karyawan satu outlet. */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('outlet_id', $outletId);
    }

    /** Urut per outlet lalu nama, supaya tampil terkelompok. */
    public function scopeOrderedByOutlet(Builder $query): Builder
    {
        return $query->orderBy(
            Outlet::select('name')->whereColumn('outlets.id', 'employees.outlet_id'),
        )->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
