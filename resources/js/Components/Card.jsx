export default function Card({ title, subtitle, action = null, children, className = '' }) {
    const hasHeader = Boolean(title || subtitle || action);

    return (
        <section className={`card ${className}`.trim()}>
            {hasHeader ? (
                <header className="card__header">
                    <div>
                        {title ? <h2 className="card__title">{title}</h2> : null}
                        {subtitle ? <p className="muted">{subtitle}</p> : null}
                    </div>
                    {action}
                </header>
            ) : null}

            {children}
        </section>
    );
}
