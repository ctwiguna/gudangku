import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import ConfirmDialog from '../../Components/ConfirmDialog';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import FormField from '../../Components/FormField';
import Modal from '../../Components/Modal';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

export default function Index({ outlets }) {
    const { auth } = usePage().props;
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState(null);

    const form = useForm({ name: '', code: '', address: '', phone: '', is_active: true });

    function openCreate() {
        form.reset();
        form.clearErrors();
        form.setData({ name: '', code: '', address: '', phone: '', is_active: true });
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(outlet) {
        form.clearErrors();
        form.setData({
            name: outlet.name,
            code: outlet.code,
            address: outlet.address ?? '',
            phone: outlet.phone ?? '',
            is_active: outlet.is_active,
        });
        setEditing(outlet);
        setFormOpen(true);
    }

    function submit(event) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                setEditing(null);
            },
        };

        if (editing) {
            form.put(`/outlets/${editing.id}`, options);

            return;
        }

        form.post('/outlets', options);
    }

    function toggleStatus(outlet) {
        setBusyId(outlet.id);
        router.patch(`/outlets/${outlet.id}/status`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/outlets/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    return (
        <AppShell
            title="Outlet"
            subtitle="Outlet nonaktif tidak muncul di form transaksi baru"
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/settings"
            actions={<Button onClick={openCreate}>Tambah outlet</Button>}
        >
            <Head title="Outlet" />

            <Card title="Daftar outlet" subtitle={`${number(outlets.length)} outlet`}>
                <DataTable
                    caption="Daftar outlet"
                    columns={[
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'contact', label: 'Kontak' },
                        { key: 'users', label: 'User', align: 'right' },
                        { key: 'transactions', label: 'Transaksi', align: 'right' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={outlets}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'outlet':
                                return (
                                    <span>
                                        {row.name}
                                        <br />
                                        <span className="muted">{row.code}</span>
                                    </span>
                                );
                            case 'contact':
                                return row.address || row.phone ? (
                                    <span>
                                        {row.address ?? '-'}
                                        <br />
                                        <span className="muted">{row.phone ?? '-'}</span>
                                    </span>
                                ) : (
                                    <span className="muted">-</span>
                                );
                            case 'users':
                                return number(row.users_count);
                            case 'transactions':
                                return (
                                    <span>
                                        {number(row.purchases_count)} beli
                                        <br />
                                        <span className="muted">
                                            {number(row.movements_count)} gerak · {number(row.opnames_count)} opname
                                        </span>
                                    </span>
                                );
                            case 'status':
                                return row.is_active ? (
                                    <Badge tone="success">Aktif</Badge>
                                ) : (
                                    <Badge tone="muted">Nonaktif</Badge>
                                );
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <button type="button" className="link" onClick={() => openEdit(row)}>
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            className="link"
                                            disabled={busyId === row.id}
                                            onClick={() => toggleStatus(row)}
                                        >
                                            {row.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                        </button>
                                        <button
                                            type="button"
                                            className="link-danger"
                                            onClick={() => setPendingDelete(row)}
                                        >
                                            Hapus
                                        </button>
                                    </span>
                                );
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada outlet"
                            description="Tambahkan outlet lebih dulu sebelum menugaskan manajer."
                            action={<Button onClick={openCreate}>Tambah outlet</Button>}
                        />
                    }
                />
            </Card>

            <Modal
                open={formOpen}
                onClose={() => setFormOpen(false)}
                title={editing ? 'Ubah outlet' : 'Tambah outlet'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setFormOpen(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="outlet-form"
                            processing={form.processing}
                            processingLabel="Menyimpan"
                        >
                            Simpan
                        </Button>
                    </>
                }
            >
                <form id="outlet-form" onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama outlet" htmlFor="outlet-name" error={form.errors.name} required>
                            <TextInput
                                id="outlet-name"
                                value={form.data.name}
                                error={Boolean(form.errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Kode outlet"
                            htmlFor="outlet-code"
                            error={form.errors.code}
                            hint="Contoh: CKR-03"
                            required
                        >
                            <TextInput
                                id="outlet-code"
                                value={form.data.code}
                                error={Boolean(form.errors.code)}
                                onChange={(event) => form.setData('code', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Alamat" htmlFor="outlet-address" error={form.errors.address}>
                            <TextInput
                                id="outlet-address"
                                value={form.data.address}
                                onChange={(event) => form.setData('address', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Telepon" htmlFor="outlet-phone" error={form.errors.phone}>
                            <TextInput
                                id="outlet-phone"
                                value={form.data.phone}
                                inputMode="tel"
                                onChange={(event) => form.setData('phone', event.target.value)}
                            />
                        </FormField>

                        <label className="checkbox">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                            />
                            <span>Aktifkan outlet ini di form transaksi baru</span>
                        </label>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus outlet"
                message="Outlet berikut akan dihapus permanen."
                itemName={pendingDelete ? `${pendingDelete.name} (${pendingDelete.code})` : null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
