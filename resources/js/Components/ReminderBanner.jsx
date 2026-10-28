import { Link } from '@inertiajs/react';
import { AlertIcon } from './Icons';

/**
 * Pengingat stock opname mingguan. Bersifat mendesak, bukan memblokir:
 * pekerjaan lain tetap bisa dilakukan.
 */
export default function ReminderBanner({ title, description = null, actionLabel = null, actionHref = null }) {
    return (
        <aside className="reminder" role="status">
            <span className="reminder__icon" aria-hidden="true">
                <AlertIcon />
            </span>

            <div className="reminder__text">
                <p className="reminder__title">{title}</p>
                {description ? <p className="reminder__desc">{description}</p> : null}
            </div>

            {actionLabel && actionHref ? (
                <Link href={actionHref} className="reminder__action">
                    {actionLabel}
                </Link>
            ) : null}
        </aside>
    );
}
