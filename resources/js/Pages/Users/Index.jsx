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
import SelectInput from '../../Components/SelectInput';
import StatCard from '../../Components/StatCard';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

const emptyForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'manajer',
    outlet_id: '',
    is_active: true,
};

export default function Index({ users, outlets, roles, summary }) {
    const { auth } = usePage().props;
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState(null);

    const form = useForm({ ...emptyForm });
    const isManagerRole = form.data.role === 'manajer';

    function openCreate() {
        form.clearErrors();
        form.setData({ ...emptyForm });
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(user) {
        form.clearErrors();
        form.setData({
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            role: user.role,
            outlet_id: user.outlet_id ? String(user.outlet_id) : '',
            is_active: user.is_active,
        });
        setEditing(user);
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
            form.put(`/users/${editing.id}`, options);

            return;
        }

        form.post('/users', options);
    }

    function toggleStatus(user) {
        setBusyId(user.id);
        router.patch(`/users/${user.id}/status`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/users/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    return (
        <AppShell
            title="User"
            subtitle="Role, penugasan outlet, dan aktivasi akun"
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/settings"
            actions={<Button onClick={openCreate}>Tambah user</Button>}
        >
            <Head title="User" />

            <div className="stat-grid">
                <StatCard label="Semua user" value={number(summary.total)} hint="akun" />
                <StatCard label="Owner" value={number(summary.owners)} hint="akses penuh" />
                <StatCard label="Manajer" value={number(summary.managers)} hint="terikat outlet" />
                <StatCard
                    label="Menunggu aktivasi"
                    value={number(summary.inactive)}
                    tone={summary.inactive > 0 ? 'accent' : 'default'}
                    hint="tidak bisa login"
                />
            </div>

            <Card title="Daftar user" subtitle="Hanya owner yang bisa mengubah role">
                <DataTable
                    caption="Daftar user"
                    columns={[
                        { key: 'user', label: 'Nama' },
                        { key: 'role', label: 'Role' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={users}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'user':
                                return (
                                    <span>
                                        {row.name}
                                        <br />
                                        <span className="muted">{row.email}</span>
                                    </span>
                                );
                            case 'role':
                                return row.role === 'owner' ? (
                                    <Badge tone="primary">{row.role_label}</Badge>
                                ) : (
                                    <Badge tone="muted">{row.role_label}</Badge>
                                );
                            case 'outlet':
                                return row.outlet ? (
                                    <span>
                                        {row.outlet}
                                        <br />
                                        <span className="muted">{row.outlet_code}</span>
                                    </span>
                                ) : (
                                    <span className="muted">Tanpa outlet</span>
                                );
                            case 'status':
                                return row.is_active ? (
                                    <Badge tone="success">Aktif</Badge>
                                ) : (
                                    <Badge tone="accent">Belum aktif</Badge>
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
                            title="Belum ada user"
                            description="Tambahkan user lalu tugaskan ke outlet."
                            action={<Button onClick={openCreate}>Tambah user</Button>}
                        />
                    }
                />
            </Card>

            <Modal
                open={formOpen}
                onClose={() => setFormOpen(false)}
                title={editing ? 'Ubah user' : 'Tambah user'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setFormOpen(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button type="submit" form="user-form" processing={form.processing} processingLabel="Menyimpan">
                            Simpan
                        </Button>
                    </>
                }
            >
                <form id="user-form" onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama" htmlFor="user-name" error={form.errors.name} required>
                            <TextInput
                                id="user-name"
                                value={form.data.name}
                                error={Boolean(form.errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Email" htmlFor="user-email" error={form.errors.email} required>
                            <TextInput
                                id="user-email"
                                type="email"
                                value={form.data.email}
                                error={Boolean(form.errors.email)}
                                onChange={(event) => form.setData('email', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Role" htmlFor="user-role" error={form.errors.role} required>
                            <SelectInput
                                id="user-role"
                                value={form.data.role}
                                options={roles}
                                error={Boolean(form.errors.role)}
                                onChange={(event) => {
                                    const role = event.target.value;
                                    form.setData({
                                        ...form.data,
                                        role,
                                        outlet_id: role === 'owner' ? '' : form.data.outlet_id,
                                    });
                                }}
                            />
                        </FormField>

                        {isManagerRole ? (
                            <FormField
                                label="Outlet"
                                htmlFor="user-outlet"
                                error={form.errors.outlet_id}
                                hint="Manajer wajib ditugaskan ke satu outlet."
                                required
                            >
                                <SelectInput
                                    id="user-outlet"
                                    value={form.data.outlet_id}
                                    placeholder="Pilih outlet"
                                    options={outlets.map((outlet) => ({
                                        value: String(outlet.id),
                                        label: `${outlet.name} (${outlet.code})`,
                                    }))}
                                    error={Boolean(form.errors.outlet_id)}
                                    onChange={(event) => form.setData('outlet_id', event.target.value)}
                                />
                            </FormField>
                        ) : (
                            <p className="muted">Owner tidak terikat pada satu outlet.</p>
                        )}

                        <FormField
                            label={editing ? 'Password baru' : 'Password'}
                            htmlFor="user-password"
                            error={form.errors.password}
                            hint={editing ? 'Kosongkan kalau tidak ingin mengubah password.' : 'Minimal 8 karakter.'}
                            required={!editing}
                        >
                            <TextInput
                                id="user-password"
                                type="password"
                                value={form.data.password}
                                autoComplete="new-password"
                                error={Boolean(form.errors.password)}
                                onChange={(event) => form.setData('password', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Ulangi password"
                            htmlFor="user-password-confirmation"
                            error={form.errors.password_confirmation}
                        >
                            <TextInput
                                id="user-password-confirmation"
                                type="password"
                                value={form.data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            />
                        </FormField>

                        <label className="checkbox">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                            />
                            <span>Aktifkan akun ini supaya bisa login</span>
                        </label>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus user"
                message="User berikut akan dihapus permanen."
                itemName={pendingDelete ? `${pendingDelete.name} (${pendingDelete.email})` : null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
