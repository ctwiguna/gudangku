export default function TextInput({
    id,
    error = false,
    hasError = false,
    className = '',
    ...props
}) {
    const invalid = error || hasError;

    return (
        <input
            {...props}
            id={id}
            className={`field__input ${className}`.trim()}
            aria-invalid={invalid || undefined}
            aria-describedby={invalid ? `${id}-error` : undefined}
        />
    );
}
