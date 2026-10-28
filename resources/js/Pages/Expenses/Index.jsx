import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import ConfirmDialog from '../../Components/ConfirmDialog';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateId, monthLabel, number, rupiah } from '../../utils/format';

export default function Index({ expenses, filters, summary, outlets, categories }) {
    const { auth } = usePage().props;
    const [month, setMonth] = useState(filters.month);
    const [outlet, setOutlet] = useState(filters.outlet ? String(filters.outlet) : '');
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    function applyFilter(event) {
        event.preventDefault();

        router.get(
            '/expenses',
            { month, outlet: outlet || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/expenses/${pendingDelete.id}`, {
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    const biggestCategory = summary.by_category[0] ?? null;

    return (
        <AppShell
            title="Pengeluaran"
            subtitle={monthLabel(filters.month)}
            badge={<Badge>{auth.user.role_label}</Badge>}
            actions={
                <Link href="/expenses/create" className="btn">
                    Catat pengeluaran
                </Link>
            }
        >
            <Head title="Pengeluaran" />

            <div className="stat-grid">
                <StatCard label="Total bulan ini" value={summary.total} money tone="accent" />
                <StatCard label="Jumlah entri" value={number(summary.count)} hint="pengeluaran" />
                <StatCard
                    label="Kategori terbesar"
                    value={biggestCategory ? biggestCategory.label : '-'}
                    hint={biggestCategory ? rupiah(biggestCategory.total) : 'belum ada data'}
                />
                <StatCard label="Outlet" value={number(outlets.length)} hint="terdaftar" />
            </div>

            <Card title="Filter" subtitle="Periode dan outlet">
                <form onSubmit={applyFilter} className="filters">
                    <label className="field">
                        <span className="field__label">Bulan</span>
                        <input
                            type="month"
                            className="field__input"
                            value={month}
                            onChange={(event) => setMonth(event.target.value)}
                        />
                    </label>

                    <label className="field">
                        <span className="field__label">Outlet</span>
                        <select
                            className="field__input"
                            value={outlet}
                            onChange={(event) => setOutlet(event.target.value)}
                        >
                            <option value="">Semua outlet</option>
                            {outlets.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <div className="filters__actions">
                        <Button type="submit">Terapkan</Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setOutlet('');
                                router.get('/expenses', {}, { preserveState: true, replace: true });
                            }}
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </Card>

            {summary.by_category.length > 0 ? (
                <Card title="Rincian per kategori" subtitle={monthLabel(filters.month)}>
                    <ul className="list">
                        {summary.by_category.map((row) => (
                            <li key={row.category} className="list__row">
                                <p className="list__title">{row.label}</p>
                                <span className="money">{rupiah(row.total)}</span>
                            </li>
                        ))}
                    </ul>
                </Card>
            ) : null}

            <Card title="Daftar pengeluaran" subtitle={`${number(expenses.total)} entri`}>
                <DataTable
                    caption="Daftar pengeluaran"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'category', label: 'Kategori' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'period', label: 'Periode' },
                        { key: 'amount', label: 'Nominal', align: 'right' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={expenses.data}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'date':
                                return dateId(row.expense_date);
                            case 'category':
                                return (
                                    <span>
                                        {row.category_label}
                                        {row.note ? (
                                            <>
                                                <br />
                                                <span className="muted">{row.note}</span>
                                            </>
                                        ) : null}
                                    </span>
                                );
                            case 'outlet':
                                return row.outlet;
                            case 'period':
                                return row.period_start && row.period_end ? (
                                    <span>
                                        {dateId(row.period_start)} – {dateId(row.period_end)}
                                    </span>
                                ) : (
                                    <span className="muted">-</span>
                                );
                            case 'amount':
                                return <span className="money">{rupiah(row.amount)}</span>;
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <Link href={`/expenses/${row.id}/edit`}>Ubah</Link>
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
                            title="Belum ada pengeluaran"
                            description={`Belum ada catatan pengeluaran pada ${monthLabel(filters.month)}.`}
                            action={
                                <Link href="/expenses/create" className="btn">
                                    Catat pengeluaran
                                </Link>
                            }
                        />
                    }
                />

                <Pagination meta={expenses} />
            </Card>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus pengeluaran"
                message="Pengeluaran berikut akan dihapus permanen:"
                itemName={
                    pendingDelete
                        ? `${pendingDelete.category_label} · ${rupiah(pendingDelete.amount)} · ${dateId(pendingDelete.expense_date)}`
                        : null
                }
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />

            <p className="muted">
                Kategori tersedia: {categories.map((item) => item.label).join(', ')}.
            </p>
        </AppShell>
    );
}
