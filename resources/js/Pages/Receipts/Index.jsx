import { Head, Link, router, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import AppShell from '../../Layouts/AppShell';
import { dateId, number, rupiah } from '../../utils/format';

function statusBadge(row) {
    if (row.is_paid) {
        return <Badge tone="success">Lunas</Badge>;
    }

    if (row.is_priced) {
        return <Badge tone="danger">Belum dibayar</Badge>;
    }

    return <Badge tone="accent">Menunggu harga</Badge>;
}

export default function Index({ receipts, canSeePrice, filters, outlets }) {
    const { auth } = usePage().props;

    const columns = [
        { key: 'date', label: 'Tanggal' },
        { key: 'supplier', label: 'Supplier' },
        { key: 'outlet', label: 'Outlet' },
        { key: 'items', label: 'Item', align: 'right' },
        { key: 'qty', label: 'Jumlah', align: 'right' },
        { key: 'status', label: 'Status' },
    ];

    if (canSeePrice) {
        columns.push({ key: 'total', label: 'Total', align: 'right' });
    }

    columns.push({ key: 'action', label: 'Aksi' });

    function applyOutlet(value) {
        router.get(
            '/receipts',
            value ? { outlet: value } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <AppShell
            title={canSeePrice ? 'Penerimaan barang' : 'Riwayat penerimaan'}
            subtitle={canSeePrice ? 'Semua outlet' : auth.user.outlet?.name ?? 'Outlet sendiri'}
            badge={<Badge>{auth.user.role_label}</Badge>}
            actions={
                <Link href="/receipts/create" className="btn">
                    Terima barang
                </Link>
            }
        >
            <Head title="Penerimaan barang" />

            {canSeePrice && outlets.length > 0 ? (
                <Card title="Filter outlet" subtitle="Owner bisa melihat semua outlet">
                    <label className="field">
                        <span className="field__label">Outlet</span>
                        <select
                            className="field__input"
                            value={filters.outlet ? String(filters.outlet) : ''}
                            onChange={(event) => applyOutlet(event.target.value)}
                        >
                            <option value="">Semua outlet</option>
                            {outlets.map((outlet) => (
                                <option key={outlet.id} value={outlet.id}>
                                    {outlet.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </Card>
            ) : null}

            <Card
                title="Daftar penerimaan"
                subtitle={
                    canSeePrice
                        ? 'Barang yang belum berharga masuk antrean owner'
                        : 'Tanpa harga dan tanpa total'
                }
            >
                <DataTable
                    caption="Daftar penerimaan barang"
                    columns={columns}
                    rows={receipts.data}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'date':
                                return (
                                    <span>
                                        {dateId(row.received_at)}
                                        {row.invoice_no ? (
                                            <>
                                                <br />
                                                <span className="muted">{row.invoice_no}</span>
                                            </>
                                        ) : null}
                                    </span>
                                );
                            case 'supplier':
                                return row.supplier;
                            case 'outlet':
                                return row.outlet;
                            case 'items':
                                return number(row.items);
                            case 'qty':
                                return number(row.qty);
                            case 'status':
                                return statusBadge(row);
                            case 'total':
                                return <span className="money">{rupiah(row.total)}</span>;
                            case 'action':
                                return <Link href={`/receipts/${row.id}`}>Lihat</Link>;
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada penerimaan"
                            description="Catat barang datang dari supplier lewat tombol Terima barang."
                            action={
                                <Link href="/receipts/create" className="btn">
                                    Terima barang
                                </Link>
                            }
                        />
                    }
                />

                <Pagination meta={receipts} />
            </Card>
        </AppShell>
    );
}
