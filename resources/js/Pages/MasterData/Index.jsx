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
import Tabs from '../../Components/Tabs';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

export default function Index({ tab, categories, suppliers }) {
    const { auth } = usePage().props;
    const [active, setActive] = useState(tab);

    const tabs = [
        { key: 'kategori', label: 'Kategori', badge: categories.length },
        { key: 'supplier', label: 'Supplier', badge: suppliers.length },
    ];

    return (
        <AppShell
            title="Kategori & supplier"
            subtitle="Master data untuk produk dan penerimaan barang"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Kategori & supplier" />

            <Tabs tabs={tabs} active={active} onChange={setActive} />

            {active === 'kategori' ? (
                <CategoryPanel categories={categories} />
            ) : (
                <SupplierPanel suppliers={suppliers} />
            )}
        </AppShell>
    );
}

function CategoryPanel({ categories }) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({ name: '', description: '' });

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(category) {
        form.clearErrors();
        form.setData({ name: category.name, description: category.description ?? '' });
        setEditing(category);
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
            form.put(`/master-data/kategori/${editing.id}`, options);

            return;
        }

        form.post('/master-data/kategori', options);
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/master-data/kategori/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    return (
        <>
            <Card
                title="Kategori produk"
                subtitle="Kategori tidak bisa dihapus selama masih dipakai produk"
                action={<Button onClick={openCreate}>Tambah kategori</Button>}
            >
                <DataTable
                    caption="Daftar kategori produk"
                    columns={[
                        { key: 'name', label: 'Nama' },
                        { key: 'description', label: 'Keterangan' },
                        { key: 'products', label: 'Produk', align: 'right' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={categories}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'name':
                                return row.name;
                            case 'description':
                                return row.description ? row.description : <span className="muted">-</span>;
                            case 'products':
                                return number(row.products_count);
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <button type="button" className="link" onClick={() => openEdit(row)}>
                                            Ubah
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
                            title="Belum ada kategori"
                            description="Tambahkan kategori sebelum membuat produk."
                            action={<Button onClick={openCreate}>Tambah kategori</Button>}
                        />
                    }
                />
            </Card>

            <Modal
                open={formOpen}
                onClose={() => setFormOpen(false)}
                title={editing ? 'Ubah kategori' : 'Tambah kategori'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setFormOpen(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="category-form"
                            processing={form.processing}
                            processingLabel="Menyimpan"
                        >
                            Simpan
                        </Button>
                    </>
                }
            >
                <form id="category-form" onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama kategori" htmlFor="category-name" error={form.errors.name} required>
                            <TextInput
                                id="category-name"
                                value={form.data.name}
                                placeholder="Contoh: Plastik packing kecil"
                                error={Boolean(form.errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Keterangan"
                            htmlFor="category-description"
                            error={form.errors.description}
                            hint="Opsional, maksimal 255 karakter."
                        >
                            <TextInput
                                id="category-description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                        </FormField>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus kategori"
                message="Kategori berikut akan dihapus permanen."
                itemName={pendingDelete?.name ?? null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </>
    );
}

function SupplierPanel({ suppliers }) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({ name: '', contact_name: '', phone: '', address: '' });

    function openCreate() {
        form.reset();
        form.clearErrors();
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
            form.put(`/master-data/supplier/${editing.id}`, options);

            return;
        }

        form.post('/master-data/supplier', options);
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/master-data/supplier/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    return (
        <>
            <Card
                title="Supplier"
                subtitle="Supplier yang sudah dipakai pembelian tidak bisa dihapus"
                action={<Button onClick={openCreate}>Tambah supplier</Button>}
            >
                <DataTable
                    caption="Daftar supplier"
                    columns={[
                        { key: 'name', label: 'Nama' },
                        { key: 'contact', label: 'Kontak' },
                        { key: 'products', label: 'Produk', align: 'right' },
                        { key: 'purchases', label: 'Pembelian', align: 'right' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={suppliers}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'name':
                                return row.name;
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
                            case 'products':
                                return number(row.products_count);
                            case 'purchases':
                                return number(row.purchases_count);
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <button type="button" className="link" onClick={() => openEdit(row)}>
                                            Ubah
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
                            title="Belum ada supplier"
                            description="Supplier dipakai pada penerimaan barang."
                            action={<Button onClick={openCreate}>Tambah supplier</Button>}
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
                                error={Boolean(form.errors.contact_name)}
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
                                error={Boolean(form.errors.address)}
                                onChange={(event) => form.setData('address', event.target.value)}
                            />
                        </FormField>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus supplier"
                message="Supplier berikut akan dihapus permanen."
                itemName={pendingDelete?.name ?? null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </>
    );
}
