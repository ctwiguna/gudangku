const formatter = new Intl.NumberFormat('id-ID');

/**
 * Input nominal rupiah. Nilai yang dikirim ke parent selalu angka mentah
 * tanpa pemisah, sedangkan yang tampil sudah berformat ribuan.
 */
export default function CurrencyInput({
    id,
    value,
    onChange,
    error = false,
    className = '',
    ...props
}) {
    const display =
        value === '' || value === null || value === undefined ? '' : formatter.format(Number(value));

    function handleChange(event) {
        const digits = event.target.value.replace(/[^0-9]/g, '');

        onChange(digits === '' ? '' : Number(digits));
    }

    return (
        <input
            {...props}
            id={id}
            type="text"
            inputMode="numeric"
            value={display}
            onChange={handleChange}
            className={`field__input money ${className}`.trim()}
            aria-invalid={error || undefined}
            aria-describedby={error ? `${id}-error` : undefined}
        />
    );
}
