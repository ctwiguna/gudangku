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

const emptyForm = {
    name: '',
    contact_name: '',
    phone: '',
    address: '',
    lead_time_days: '',
    term_of_payment: '',
};

export default function Index({ suppliers, openCreate = false }) {
    const { auth } = usePage().props;
    const [formOpen, setFormOpen] = useState(openCreate);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [selected, setSelected] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({ ...emptyForm });

    function openCreateForm() {
        form.clearErrors();
        form.setData({ ...emptyForm });
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(supplier) {
        form.clearErrors();
        form.setData({
            name: supplier.name,
            contact_name: supplier.contact_name ?? '',
            phone: supplier.phone ?? '',
            address: supplier.address ?? '',
            lead_time_days: supplier.lead_time_days ?? '',
            term_of_payment: supplier.term_of_payment ?? '',
        });
        setEditing(supplier);
        setFormOpen(true);
    }

    function submit(event) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                setEditing(null);
                form.reset();
            },
        };

        if (editing) {
            form.put(`/suppliers/${editing.id}`, options);

            return;
        }

        form.post('/suppliers', options);
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/suppliers/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
                setSelected(null);
            },
        });
    }

    return (
        <AppShell
            title="Database Supplier"
            subtitle="Informasi lengkap pemasok agar mudah dihubungi dan dibandingkan"
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/warehouse"
            actions={
                <div className="toolbar">
                    <Button onClick={openCreateForm}>Tambah supplier</Button>
                    <Button
                        variant="outline"
                        disabled={selected === null}
                        title={selected === null ? 'Pilih baris dulu' : undefined}
                        onClick={() => openEdit(selected)}
                    >
                        Ubah
                    </Button>
                    <Button
                        variant="outline"
                        className="btn--danger"
                        disabled={selected === null}
                        title={selected === null ? 'Pilih baris dulu' : undefined}
                        onClick={() => setPendingDelete(selected)}
                    >
                        Hapus
                    </Button>
                </div>
            }
        >
            <Head title="Database Supplier" />

            <Card title="Daftar supplier" subtitle={`${number(suppliers.length)} supplier terdaftar`}>
                <DataTable
                    caption="Database supplier"
                    columns={[
                        { key: 'select', label: '' },
                        { key: 'id', label: 'ID', align: 'right' },
                        { key: 'name', label: 'Nama Supplier' },
                        { key: 'contact', label: 'Kontak/Telepon' },
                        { key: 'address', label: 'Alamat' },
                        { key: 'lead_time', label: 'Lead Time' },
                        { key: 'top', label: 'TOP' },
                    ]}
                    rows={suppliers}
                    rowClassName={(row) => (selected?.id === row.id ? 'is-selected' : undefined)}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'id':
                                return row.id;
                            case 'name':
                                return (
                                    <span>
                                        {row.name}
                                        <br />
                                        <span className="muted">
                                            {number(row.products_count)} barang · {number(row.purchases_count)} pembelian
                                        </span>
                                    </span>
                                );
                            case 'contact':
                                return row.contact_name || row.phone ? (
                                    <span>
                                        {row.contact_name ?? '-'}
                                        <br />
                                        <span className="muted">{row.phone ?? '-'}</span>
                                    </span>
                                ) : (
                                    <span className="muted">-</span>
                                );
                            case 'address':
                                return row.address ?? <span className="muted">-</span>;
                            case 'lead_time':
                                return row.lead_time_days === null ? (
                                    <span className="muted">-</span>
                                ) : (
                                    `${number(row.lead_time_days)} hari`
                                );
                            case 'top':
                                return row.term_of_payment ?? <span className="muted">-</span>;
                            case 'select':
                                return (
                                    <label className="row-select">
                                        <input
                                            type="radio"
                                            name="pilih-baris"
                                            checked={selected?.id === row.id}
                                            onChange={() => setSelected(row)}
                                            aria-label={`Pilih ${row.name}`}
                                        />
                                    </label>
                                );

                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada supplier"
                            description="Tambahkan supplier beserta kontak, lead time, dan term of payment."
                            action={<Button onClick={openCreateForm}>Tambah supplier</Button>}
                        />
                    }
                />
            </Card>

            <Modal
                open={formOpen}
                onClose={() => setFormOpen(false)}
                title={editing ? 'Ubah supplier' : 'Tambah supplier'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setFormOpen(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="supplier-form"
                            processing={form.processing}
                            processingLabel="Menyimpan"
                        >
                            Simpan
                        </Button>
                    </>
                }
            >
                <form id="supplier-form" onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama supplier" htmlFor="supplier-name" error={form.errors.name} required>
                            <TextInput
                                id="supplier-name"
                                value={form.data.name}
                                error={Boolean(form.errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Nama kontak" htmlFor="supplier-contact" error={form.errors.contact_name}>
                            <TextInput
                                id="supplier-contact"
                                value={form.data.contact_name}
                                onChange={(event) => form.setData('contact_name', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Telepon" htmlFor="supplier-phone" error={form.errors.phone}>
                            <TextInput
                                id="supplier-phone"
                                value={form.data.phone}
                                inputMode="tel"
                                error={Boolean(form.errors.phone)}
                                onChange={(event) => form.setData('phone', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Alamat" htmlFor="supplier-address" error={form.errors.address}>
                            <TextInput
                                id="supplier-address"
                                value={form.data.address}
                                onChange={(event) => form.setData('address', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Lead time (hari pengiriman)"
                            htmlFor="supplier-lead-time"
                            error={form.errors.lead_time_days}
                            hint="Rata-rata lama barang sampai setelah dipesan."
                        >
                            <TextInput
                                id="supplier-lead-time"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                value={form.data.lead_time_days}
                                error={Boolean(form.errors.lead_time_days)}
                                onChange={(event) => form.setData('lead_time_days', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Term of payment (TOP)"
                            htmlFor="supplier-top"
                            error={form.errors.term_of_payment}
                            hint="Contoh: COD, NET 14, NET 30."
                        >
                            <TextInput
                                id="supplier-top"
                                value={form.data.term_of_payment}
                                error={Boolean(form.errors.term_of_payment)}
                                onChange={(event) => form.setData('term_of_payment', event.target.value)}
                            />
                        </FormField>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus supplier"
                message="Supplier berikut akan dihapus permanen."
                itemName={pendingDelete ? `${pendingDelete.name}` : null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
