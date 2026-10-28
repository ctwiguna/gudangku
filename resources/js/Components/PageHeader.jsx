import { Link } from '@inertiajs/react';
import { ChevronLeftIcon } from './Icons';

export default function PageHeader({ title, subtitle = null, badge = null, backHref = null, actions = null }) {
    return (
        <header className="page-header">
            <div className="page-header__row">
                {backHref ? (
                    <Link href={backHref} className="icon-btn" aria-label="Kembali">
                        <ChevronLeftIcon />
                    </Link>
                ) : null}

                <div className="page-header__text">
                    {badge ? <div className="page-header__badge">{badge}</div> : null}
                    <h1 className="page-header__title">{title}</h1>
                    {subtitle ? <p className="muted">{subtitle}</p> : null}
                </div>
            </div>

            {actions ? <div className="page-header__actions">{actions}</div> : null}
        </header>
    );
}
