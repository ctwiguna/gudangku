<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Listrik = 'listrik';
    case Air = 'air';
    case Ipal = 'ipal';
    case Gaji = 'gaji';
    case Sewa = 'sewa';
    case Lain = 'lain';

    public function label(): string
    {
        return match ($this) {
            self::Listrik => 'Listrik',
            self::Air => 'Air (PDAM)',
            self::Ipal => 'Iuran IPAL',
            self::Gaji => 'Gaji mingguan',
            self::Sewa => 'Sewa',
            self::Lain => 'Lain-lain',
        };
    }

    /** Gaji wajib menyebutkan periode minggu. */
    public function requiresPeriod(): bool
    {
        return $this === self::Gaji;
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
