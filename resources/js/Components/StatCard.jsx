import { Link } from '@inertiajs/react';
import { rupiah } from '../utils/format';

/**
 * Kartu angka. `money` dipakai untuk nilai uang supaya memakai tabular nums
 * dan rata kanan. Angka penting terlihat tanpa scroll (prinsip design system).
 *
 * Kalau `href` diisi, seluruh kartu bisa diklik dan menampilkan ajakan
 * "Klik lebih detail" di bagian bawah.
 */
export default function StatCard({
    label,
    value,
    hint = null,
    tone = 'default',
    money = false,
    icon = null,
    href = null,
    moreLabel = null,
}) {
    const display = money ? rupiah(value) : value;

    const className = `stat stat--${tone}${money ? ' stat--money' : ''}${href ? ' stat--link' : ''}`;

    const body = (
        <>
            <div className="stat__head">
                <p className="stat__label">{label}</p>
                {icon}
            </div>

            <p className={money ? 'stat__value money' : 'stat__value'}>{display}</p>

            {hint ? <p className="stat__hint">{hint}</p> : null}

            {href && moreLabel ? <span className="stat__more">{moreLabel}</span> : null}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {body}
            </Link>
        );
    }

    return <div className={className}>{body}</div>;
}
