import { useEffect, useRef } from 'react';
import { CloseIcon } from './Icons';

const FOCUSABLE =
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

/**
 * Modal yang berubah menjadi bottom sheet di layar di bawah 640 piksel.
 * Menahan fokus di dalam dialog dan bisa ditutup dengan Esc.
 */
export default function Modal({ open, onClose, title, children, footer = null }) {
    const panelRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const panel = panelRef.current;
        const previousActive = document.activeElement;

        panel?.querySelector(FOCUSABLE)?.focus();

        function handleKeyDown(event) {
            if (event.key === 'Escape') {
                event.stopPropagation();
                onClose();

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
    }, [open, onClose]);

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
