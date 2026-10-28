export default function EmptyState({ title, description = null, action = null, icon = null }) {
    return (
        <div className="empty">
            <div className="empty__icon" aria-hidden="true">
                {icon ?? (
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                        <path d="M4 7h16v12H4z" strokeLinejoin="round" />
                        <path d="M4 11h16" strokeLinecap="round" />
                    </svg>
                )}
            </div>

            <h3 className="empty__title">{title}</h3>
            {description ? <p className="muted">{description}</p> : null}
            {action ? <div className="empty__action">{action}</div> : null}
        </div>
    );
}
