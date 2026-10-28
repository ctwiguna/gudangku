import Button from './Button';
import Modal from './Modal';

/**
 * Konfirmasi yang menyebut nama data, bukan pertanyaan umum.
 * Tombol konfirmasi memakai tone danger untuk aksi hapus.
 */
export default function ConfirmDialog({
    open,
    title = 'Konfirmasi',
    message,
    itemName = null,
    confirmLabel = 'Hapus',
    cancelLabel = 'Batal',
    tone = 'danger',
    processing = false,
    onConfirm,
    onCancel,
}) {
    return (
        <Modal
            open={open}
            onClose={onCancel}
            title={title}
            footer={
                <>
                    <Button variant="outline" onClick={onCancel} disabled={processing}>
                        {cancelLabel}
                    </Button>
                    <Button
                        onClick={onConfirm}
                        processing={processing}
                        processingLabel="Memproses"
                        style={tone === 'danger' ? { background: 'var(--color-danger)' } : undefined}
                    >
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            <p>{message}</p>

            {itemName ? (
                <p className="confirm__item">
                    <strong>{itemName}</strong>
                </p>
            ) : null}

            <p className="muted">Tindakan ini tidak bisa dibatalkan.</p>
        </Modal>
    );
}
