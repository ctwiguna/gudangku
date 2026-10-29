<?php

namespace App\Enums;

enum SalaryUnit: string
{
    case Harian = 'harian';
    case Mingguan = 'mingguan';
    case Bulanan = 'bulanan';

    public function label(): string
    {
        return match ($this) {
            self::Harian => 'Per hari',
            self::Mingguan => 'Per minggu',
            self::Bulanan => 'Per bulan',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
