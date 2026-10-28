export default function DateInput({ id, error = false, className = '', ...props }) {
    return (
        <input
            {...props}
            id={id}
            type="date"
            className={`field__input ${className}`.trim()}
            aria-invalid={error || undefined}
            aria-describedby={error ? `${id}-error` : undefined}
        />
    );
}
