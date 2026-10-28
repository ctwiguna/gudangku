import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from './Icons';

/**
 * Pagination untuk data Laravel. Hanya menampilkan tautan halaman
 * sebelumnya dan berikutnya plus posisi data, cukup untuk layar ponsel.
 */
export default function Pagination({ meta, only = [] }) {
    if (!meta || meta.last_page <= 1) {
        return null;
    }

    const hasPrev = meta.current_page > 1;
    const hasNext = meta.current_page < meta.last_page;

    return (
        <nav className="pagination" aria-label="Navigasi halaman">
            {hasPrev ? (
                <Link
                    href={meta.links[0]?.url ?? '#'}
                    className="pagination__btn"
                    preserveScroll
                    only={only}
                >
                    <ChevronLeftIcon />
                    <span>Sebelumnya</span>
                </Link>
            ) : (
                <span className="pagination__btn is-disabled" aria-disabled="true">
                    <ChevronLeftIcon />
                    <span>Sebelumnya</span>
                </span>
            )}

            <p className="pagination__info">
                {meta.from ?? 0}–{meta.to ?? 0} dari {meta.total ?? 0}
                <br />
                <span className="muted">
                    Halaman {meta.current_page} dari {meta.last_page}
                </span>
            </p>

            {hasNext ? (
                <Link
                    href={meta.links[meta.links.length - 1]?.url ?? '#'}
                    className="pagination__btn"
                    preserveScroll
                    only={only}
                >
                    <span>Berikutnya</span>
                    <ChevronRightIcon />
                </Link>
            ) : (
                <span className="pagination__btn is-disabled" aria-disabled="true">
                    <span>Berikutnya</span>
                    <ChevronRightIcon />
                </span>
            )}
        </nav>
    );
}
