import { usePage } from '@inertiajs/react';

/**
 * Pesan sukses dan error dari flash session.
 * aria-live polite supaya pembaca layar mengumumkan tanpa memotong ucapan lain.
 */
export default function Toast() {
    const { flash } = usePage().props;
    const error = flash?.error;
    const success = flash?.success;

    if (!error && !success) {
        return null;
    }

    return (
        <div
            className={`toast ${error ? 'toast--error' : 'toast--success'}`}
            role="status"
            aria-live="polite"
        >
            {error ?? success}
        </div>
    );
}
