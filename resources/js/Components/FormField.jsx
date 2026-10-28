export default function FormField({ label, htmlFor, error, hint, required = false, children }) {
    return (
        <div className={error ? 'field field--error' : 'field'}>
            <label className="field__label" htmlFor={htmlFor}>
                {label}
                {required ? <span aria-hidden="true"> *</span> : null}
            </label>

            {children}

            {hint && !error ? <p className="field__hint">{hint}</p> : null}

            {error ? (
                <p className="field__error" id={`${htmlFor}-error`} role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
