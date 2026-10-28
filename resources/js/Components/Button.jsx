export default function Button({
    variant = 'primary',
    processing = false,
    processingLabel = 'Memproses',
    children,
    className = '',
    ...props
}) {
    const classes = ['btn', variant === 'outline' ? 'btn--outline' : '', className]
        .filter(Boolean)
        .join(' ');

    return (
        <button
            {...props}
            className={classes}
            disabled={processing || props.disabled}
            aria-busy={processing}
        >
            {processing ? <span className="btn__spinner" aria-hidden="true" /> : null}
            <span>{processing ? processingLabel : children}</span>
        </button>
    );
}
