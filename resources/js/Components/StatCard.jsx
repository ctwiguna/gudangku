import { rupiah } from '../utils/format';

/**
 * Kartu angka. `money` dipakai untuk nilai uang supaya memakai tabular nums
 * dan rata kanan. Angka penting terlihat tanpa scroll (prinsip design system).
 */
export default function StatCard({ label, value, hint = null, tone = 'default', money = false, icon = null }) {
    const display = money ? rupiah(value) : value;

    return (
        // Kartu uang memakai kelas tambahan: lebarnya dibuat lebih lebar
        // supaya nominal rupiah tidak keluar dari batas kartu.
        <div className={`stat stat--${tone}${money ? ' stat--money' : ''}`}>
            <div className="stat__head">
                <p className="stat__label">{label}</p>
                {icon}
            </div>

            <p className={money ? 'stat__value money' : 'stat__value'}>{display}</p>

            {hint ? <p className="stat__hint">{hint}</p> : null}
        </div>
    );
}
