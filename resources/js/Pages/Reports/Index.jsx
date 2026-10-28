import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateId, number, rupiah } from '../../utils/format';

export default function Index({ filters, outlets, summary, expensesByCategory, expensesByOutlet, recentExpenses }) {
    const { auth } = usePage().props;
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [outlet, setOutlet] = useState(filters.outlet ? String(filters.outlet) : '');

    function applyFilter(event) {
        event.preventDefault();

        router.get(
            '/reports',
            { from, to, outlet: outlet || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <AppShell
            title="Laporan"
            subtitle={`${dateId(filters.from)} – ${dateId(filters.to)}`}
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Laporan" />

            <div className="stat-grid">
                <StatCard
                    label="Pengeluaran periode"
                    value={summary.expenses_total}
                    money
                    tone="accent"
                    hint={`${number(summary.expenses_count)} entri`}
                />
                <StatCard
                    label="Hutang supplier"
                    value={summary.debt_total}
                    money
                    tone="danger"
                    hint={`${number(summary.debt_count)} pembelian`}
                />
                <StatCard
                    label="Pembelian dihargai"
                    value={summary.purchases_total}
                    money
                    hint={`${number(summary.purchases_count)} pembelian`}
                />
                <StatCard label="Nilai stok" value={summary.stock_value} money />
            </div>

            <Card title="Filter laporan" subtitle="Periode dan outlet">
                <form onSubmit={applyFilter} className="filters">
                    <label className="field">
                        <span className="field__label">Dari tanggal</span>
                        <input
                            type="date"
                            className="field__input"
                            value={from}
                            onChange={(event) => setFrom(event.target.value)}
                        />
                    </label>

                    <label className="field">
                        <span className="field__label">Sampai tanggal</span>
                        <input
                            type="date"
                            className="field__input"
                            value={to}
                            onChange={(event) => setTo(event.target.value)}
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
                            {outlets.map((row) => (
                                <option key={row.id} value={row.id}>
                                    {row.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <div className="filters__actions">
                        <Button type="submit">Terapkan</Button>
                    </div>
                </form>
            </Card>

            <div className="grid grid--2">
                <Card title="Pengeluaran per kategori" subtitle="Periode terpilih">
                    {expensesByCategory.length === 0 ? (
                        <EmptyState title="Belum ada pengeluaran" description="Tidak ada data pada periode ini." />
                    ) : (
                        <ul className="list">
                            {expensesByCategory.map((row) => (
                                <li key={row.label} className="list__row">
                                    <p className="list__title">{row.label}</p>
                                    <span className="money">{rupiah(row.total)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Pengeluaran per outlet" subtitle="Periode terpilih">
                    {expensesByOutlet.length === 0 ? (
                        <EmptyState title="Belum ada pengeluaran" description="Tidak ada data pada periode ini." />
                    ) : (
                        <ul className="list">
                            {expensesByOutlet.map((row) => (
                                <li key={row.outlet} className="list__row">
                                    <p className="list__title">{row.outlet}</p>
                                    <span className="money">{rupiah(row.total)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card title="Pengeluaran terbaru pada periode" subtitle="Sepuluh entri terakhir">
                <DataTable
                    caption="Pengeluaran pada periode terpilih"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'category', label: 'Kategori' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'note', label: 'Catatan' },
                        { key: 'amount', label: 'Nominal', align: 'right' },
                    ]}
                    rows={recentExpenses}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'date':
                                return dateId(row.expense_date);
                            case 'category':
                                return row.category_label;
                            case 'outlet':
                                return row.outlet;
                            case 'note':
                                return row.note ?? <span className="muted">-</span>;
                            case 'amount':
                                return <span className="money">{rupiah(row.amount)}</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={<EmptyState title="Belum ada pengeluaran" description="Tidak ada data pada periode ini." />}
                />
            </Card>

            <p className="muted">
                Hutang supplier dan nilai stok adalah posisi saat ini, tidak dibatasi periode.
            </p>
        </AppShell>
    );
}
