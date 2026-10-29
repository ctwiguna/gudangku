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

/**
 * Halaman kategori produk. Supplier punya halaman sendiri (Database Supplier).
 */
export default function Index({ categories, openCreate = false }) {
    const { auth } = usePage().props;
    const [formOpen, setFormOpen] = useState(openCreate);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({ name: '', description: '' });

    function openCreateForm() {
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
        <AppShell
            title="Kategori produk"
            subtitle="Kategori tidak bisa dihapus selama masih dipakai barang"
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/warehouse"
            actions={<Button onClick={openCreateForm}>Tambah kategori</Button>}
        >
            <Head title="Kategori produk" />

            <Card title="Daftar kategori" subtitle={`${number(categories.length)} kategori`}>
                <DataTable
                    caption="Daftar kategori produk"
                    columns={[
                        { key: 'id', label: 'ID', align: 'right' },
                        { key: 'name', label: 'Nama Kategori' },
                        { key: 'description', label: 'Keterangan' },
                        { key: 'products', label: 'Barang', align: 'right' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={categories}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'id':
                                return row.id;
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
                            description="Tambahkan kategori sebelum membuat barang."
                            action={<Button onClick={openCreateForm}>Tambah kategori</Button>}
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
        </AppShell>
    );
}
