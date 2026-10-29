import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import ConfirmDialog from '../../Components/ConfirmDialog';
import CurrencyInput from '../../Components/CurrencyInput';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import FormField from '../../Components/FormField';
import Modal from '../../Components/Modal';
import SelectInput from '../../Components/SelectInput';
import StatCard from '../../Components/StatCard';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { number, rupiah } from '../../utils/format';

const emptyForm = {
    name: '',
    salary: '',
    salary_unit: 'bulanan',
    is_active: true,
    note: '',
};

export default function Index({ employees, salaryUnits, summary }) {
    const { auth } = usePage().props;
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [selected, setSelected] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState(null);

    const form = useForm({ ...emptyForm });

    function openCreate() {
        form.clearErrors();
        form.setData({ ...emptyForm });
        setEditing(null);
        form.setData('salary', '');
        setFormOpen(true);
    }

    function openEdit(employee) {
        form.clearErrors();
        form.setData({
            name: employee.name,
            salary: Number(employee.salary),
            salary_unit: employee.salary_unit,
            is_active: employee.is_active,
            note: employee.note ?? '',
        });
        setEditing(employee);
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
            form.put(`/employees/${editing.id}`, options);

            return;
        }

        form.post('/employees', options);
    }

    function toggleStatus(employee) {
        setBusyId(employee.id);
        router.patch(`/employees/${employee.id}/status`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/employees/${pendingDelete.id}`, {
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
            title="Kelola Karyawan"
            subtitle="Data acuan gaji. Pengeluaran gaji tetap dicatat di menu Pengeluaran."
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/warehouse"
            actions={
                <div className="toolbar">
                    <Button onClick={openCreate}>Tambah karyawan</Button>
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
            <Head title="Kelola Karyawan" />

            <div className="stat-grid">
                <StatCard label="Karyawan" value={number(summary.total)} hint="terdaftar" />
                <StatCard label="Aktif" value={number(summary.active)} hint="masih bekerja" />
            </div>

            <Card title="Daftar karyawan" subtitle={`${number(employees.length)} karyawan`}>
                <DataTable
                    caption="Daftar karyawan"
                    columns={[
                        { key: 'select', label: '' },
                        { key: 'id', label: 'ID', align: 'right' },
                        { key: 'name', label: 'Nama Karyawan' },
                        { key: 'salary', label: 'Gaji', align: 'right' },
                        { key: 'unit', label: 'Satuan Gaji' },
                        { key: 'status', label: 'Status' },
                    ]}
                    rows={employees}
                    rowClassName={(row) => (selected?.id === row.id ? 'is-selected' : undefined)}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'id':
                                return row.id;
                            case 'name':
                                return (
                                    <span>
                                        {row.name}
                                        {row.note ? (
                                            <>
                                                <br />
                                                <span className="muted">{row.note}</span>
                                            </>
                                        ) : null}
                                    </span>
                                );
                            case 'salary':
                                return <span className="money">{rupiah(row.salary)}</span>;
                            case 'unit':
                                return row.salary_unit_label;
                            case 'status':
                                return row.is_active ? (
                                    <Badge tone="success">Aktif</Badge>
                                ) : (
                                    <Badge tone="muted">Nonaktif</Badge>
                                );
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
                            title="Belum ada karyawan"
                            description="Tambahkan nama karyawan, gaji, dan satuan gajinya."
                            action={<Button onClick={openCreate}>Tambah karyawan</Button>}
                        />
                    }
                />
            </Card>

            <Modal
                open={formOpen}
                onClose={() => setFormOpen(false)}
                title={editing ? 'Ubah karyawan' : 'Tambah karyawan'}
                footer={
                    <>
                        <Button variant="outline" onClick={() => setFormOpen(false)} disabled={form.processing}>
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            form="employee-form"
                            processing={form.processing}
                            processingLabel="Menyimpan"
                        >
                            Simpan
                        </Button>
                    </>
                }
            >
                <form id="employee-form" onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama karyawan" htmlFor="employee-name" error={form.errors.name} required>
                            <TextInput
                                id="employee-name"
                                value={form.data.name}
                                error={Boolean(form.errors.name)}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Gaji" htmlFor="employee-salary" error={form.errors.salary} required>
                            <CurrencyInput
                                id="employee-salary"
                                value={form.data.salary}
                                placeholder="0"
                                error={Boolean(form.errors.salary)}
                                onChange={(value) => form.setData('salary', value)}
                            />
                        </FormField>

                        <FormField
                            label="Satuan gaji"
                            htmlFor="employee-unit"
                            error={form.errors.salary_unit}
                            required
                        >
                            <SelectInput
                                id="employee-unit"
                                value={form.data.salary_unit}
                                options={salaryUnits}
                                error={Boolean(form.errors.salary_unit)}
                                onChange={(event) => form.setData('salary_unit', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Catatan" htmlFor="employee-note" error={form.errors.note}>
                            <TextInput
                                id="employee-note"
                                value={form.data.note}
                                placeholder="Contoh: bagian cuci"
                                onChange={(event) => form.setData('note', event.target.value)}
                            />
                        </FormField>

                        <label className="checkbox">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                            />
                            <span>Karyawan masih aktif bekerja</span>
                        </label>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus karyawan"
                message="Data karyawan berikut akan dihapus permanen."
                itemName={pendingDelete ? `${pendingDelete.name} · ${rupiah(pendingDelete.salary)} ${pendingDelete.salary_unit_label}` : null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
