import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateTimeId, number, signedNumber } from '../../utils/format';

function typeTone(type) {
    if (type === 'in') {
        return 'success';
    }

    if (type === 'out') {
        return 'danger';
    }

    return 'accent';
}

export default function Index({ movements, filters, outlets, products, types, summary, scopeLabel }) {
    const { auth } = usePage().props;
    const [outlet, setOutlet] = useState(filters.outlet ? String(filters.outlet) : '');
    const [product, setProduct] = useState(filters.product ? String(filters.product) : '');
    const [type, setType] = useState(filters.type ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    function applyFilter(event) {
        event.preventDefault();

        router.get(
            '/stock-movements',
            {
                outlet: outlet || undefined,
                product: product || undefined,
                type: type || undefined,
                from: from || undefined,
                to: to || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function resetFilter() {
        setOutlet('');
        setProduct('');
        setType('');
        setFrom('');
        setTo('');
        router.get('/stock-movements', {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <AppShell
            title="Riwayat stok"
            subtitle={`${scopeLabel} · ledger pergerakan`}
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Riwayat stok" />

            <div className="stat-grid">
                <StatCard label="Barang masuk" value={number(summary.in)} tone="success" hint="unit" />
                <StatCard label="Barang keluar" value={number(summary.out)} tone="danger" hint="unit" />
                <StatCard
                    label="Penyesuaian"
                    value={signedNumber(summary.adjustment)}
                    tone="accent"
                    hint="selisih opname"
                />
                <StatCard label="Baris pergerakan" value={number(summary.rows)} hint="sesuai filter" />
            </div>

            <Card title="Filter" subtitle="Outfit, produk, jenis, dan periode">
                <form onSubmit={applyFilter} className="filters">
                    {outlets.length > 0 ? (
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
                    ) : null}

                    <label className="field">
                        <span className="field__label">Produk</span>
                        <select
                            className="field__input"
                            value={product}
                            onChange={(event) => setProduct(event.target.value)}
                        >
                            <option value="">Semua produk</option>
                            {products.map((row) => (
                                <option key={row.id} value={row.id}>
                                    {row.name} ({row.sku})
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="field">
                        <span className="field__label">Jenis</span>
                        <select className="field__input" value={type} onChange={(event) => setType(event.target.value)}>
                            <option value="">Semua jenis</option>
                            {types.map((row) => (
                                <option key={row.value} value={row.value}>
                                    {row.label}
                                </option>
                            ))}
                        </select>
                    </label>

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

                    <div className="filters__actions">
                        <Button type="submit">Terapkan</Button>
                        <Button type="button" variant="outline" onClick={resetFilter}>
                            Reset
                        </Button>
                    </div>
                </form>
            </Card>

            <Card
                title="Pergerakan stok"
                subtitle="Saldo stok adalah jumlah kolom jumlah pada tabel ini"
            >
                <DataTable
                    caption="Riwayat pergerakan stok"
                    columns={[
                        { key: 'time', label: 'Waktu' },
                        { key: 'product', label: 'Produk' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'type', label: 'Jenis' },
                        { key: 'quantity', label: 'Jumlah', align: 'right' },
                        { key: 'user', label: 'Petugas' },
                        { key: 'note', label: 'Catatan' },
                    ]}
                    rows={movements.data}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'time':
                                return dateTimeId(row.moved_at);
                            case 'product':
                                return (
                                    <span>
                                        {row.product}
                                        <br />
                                        <span className="muted">
                                            {row.sku} · {row.unit}
                                        </span>
                                    </span>
                                );
                            case 'outlet':
                                return (
                                    <span>
                                        {row.outlet}
                                        <br />
                                        <span className="muted">{row.outlet_code}</span>
                                    </span>
                                );
                            case 'type':
                                return <Badge tone={typeTone(row.type)}>{row.type_label}</Badge>;
                            case 'quantity':
                                return (
                                    <span className={row.quantity < 0 ? 'is-warn' : 'is-ok'}>
                                        {signedNumber(row.quantity)}
                                    </span>
                                );
                            case 'user':
                                return row.user;
                            case 'note':
                                return row.note ?? <span className="muted">-</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada pergerakan"
                            description="Pergerakan muncul dari penerimaan barang, pembatalan, dan penyesuaian opname."
                        />
                    }
                />

                <Pagination meta={movements} />
            </Card>
        </AppShell>
    );
}
