const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

const numberFormatter = new Intl.NumberFormat('id-ID');

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

/** Rupiah tanpa desimal, contoh: Rp1.500.000 */
export function rupiah(value) {
    return rupiahFormatter.format(Number(value ?? 0));
}

/** Angka biasa dengan pemisah ribuan, contoh: 1.250 */
export function number(value) {
    return numberFormatter.format(Number(value ?? 0));
}

/** Tanggal singkat Indonesia, contoh: 28 Okt 2026 */
export function dateId(value) {
    if (!value) {
        return '-';
    }

    return dateFormatter.format(new Date(value));
}

/** Tanggal dan jam, contoh: 28 Okt 2026 16.30 */
export function dateTimeId(value) {
    if (!value) {
        return '-';
    }

    return dateTimeFormatter.format(new Date(value));
}

/** Tanda selisih stok: +2, -4, atau 0 */
export function signedNumber(value) {
    const numeric = Number(value ?? 0);

    return numeric > 0 ? `+${numberFormatter.format(numeric)}` : numberFormatter.format(numeric);
}

/** Nama bulan Indonesia untuk filter, contoh: Oktober 2026 */
export function monthLabel(value) {
    if (!value) {
        return '-';
    }

    const [year, month] = value.split('-');

    return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(
        new Date(Number(year), Number(month) - 1, 1),
    );
}
