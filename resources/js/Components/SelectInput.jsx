export default function SelectInput({
    id,
    options = [],
    placeholder = null,
    error = false,
    className = '',
    children,
    ...props
}) {
    return (
        <select
            {...props}
            id={id}
            className={`field__input ${className}`.trim()}
            aria-invalid={error || undefined}
            aria-describedby={error ? `${id}-error` : undefined}
        >
            {placeholder ? <option value="">{placeholder}</option> : null}

            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}

            {children}
        </select>
    );
}
