/**
 * Loading data memakai skeleton, bukan spinner penuh layar.
 * Dibungkus role="status" dengan teks tersembunyi untuk pembaca layar.
 */
export default function Skeleton({ lines = 3, height = 16, label = 'Memuat data' }) {
    return (
        <div className="skeleton" role="status">
            <span className="sr-only">{label}</span>

            {Array.from({ length: lines }).map((_, index) => (
                <span
                    key={index}
                    className="skeleton__line"
                    style={{ height }}
                    aria-hidden="true"
                />
            ))}
        </div>
    );
}
