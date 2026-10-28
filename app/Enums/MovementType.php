<?php

namespace App\Enums;

enum MovementType: string
{
    /** Penerimaan barang dari supplier. */
    case In = 'in';

    /** Pembatalan penerimaan barang. */
    case Out = 'out';

    /** Penyesuaian hasil stock opname dan koreksi jumlah. */
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Masuk',
            self::Out => 'Keluar',
            self::Adjustment => 'Penyesuaian',
        };
    }
}
