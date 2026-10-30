import { useEffect, useRef } from 'react';
import { CloseIcon } from './Icons';

const FOCUSABLE =
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

/** Field yang diprioritaskan menerima fokus saat modal dibuka. */
const FIRST_FIELD =
    'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])';

/**
 * Modal yang berubah menjadi bottom sheet di layar di bawah 640 piksel.
 * Menahan fokus di dalam dialog dan bisa ditutup dengan Esc.
 *
 * Catatan penting: effect pemasangan listener HANYA bergantung pada `open`.
 * Kalau `onClose` dijadikan dependensi, setiap render (termasuk tiap ketikan
 * di form) akan memasang ulang listener dan merebut fokus ke elemen pertama,
 * sehingga fokus berpindah ke tombol tutup dan modal ikut tertutup saat
 * pengguna mengetik. Karena itu `onClose` disimpan di ref.
 */
export default function Modal({ open, onClose, title, children, footer = null }) {
    const panelRef = useRef(null);
    const closeRef = useRef(onClose);

    // Selalu simpan handler terbaru tanpa memicu ulang effect.
    useEffect(() => {
        closeRef.current = onClose;
    });

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const panel = panelRef.current;
        const previousActive = document.activeElement;

        // Fokus ke field pertama; tombol tutup hanya dipakai kalau tidak ada field.
        const firstField = panel?.querySelector(FIRST_FIELD);
        (firstField ?? panel?.querySelector(FOCUSABLE) ?? panel)?.focus?.();

        function handleKeyDown(event) {
            if (event.key === 'Escape') {
                event.stopPropagation();
                closeRef.current?.();

                return;
            }

            if (event.key !== 'Tab' || !panel) {
                return;
            }

            const focusable = Array.from(panel.querySelectorAll(FOCUSABLE)).filter(
                (element) => element.offsetParent !== null,
            );

            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', handleKeyDown);
        document.body.classList.add('is-modal-open');

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.classList.remove('is-modal-open');
            previousActive?.focus?.();
        };
    }, [open]);

    if (!open) {
        return null;
    }

    return (
        <div className="modal">
            <div className="modal__backdrop" onClick={onClose} aria-hidden="true" />

            <div
                className="modal__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
                ref={panelRef}
            >
                <header className="modal__header">
                    <h2 className="modal__title" id="modal-title">
                        {title}
                    </h2>

                    <button type="button" className="icon-btn" onClick={onClose} aria-label="Tutup">
                        <CloseIcon />
                    </button>
                </header>

                <div className="modal__body">{children}</div>

                {footer ? <footer className="modal__footer">{footer}</footer> : null}
            </div>
        </div>
    );
}
