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
import { dateId, number, rupiah } from '../../utils/format';

export default function Index({ queue, purchases, summary, outlets, filters }) {
    const { auth } = usePage().props;
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState(null);

    function applyOutlet(value) {
        router.get(
            '/purchases',
            value ? { outlet: value } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function markPaid(row) {
        setBusyId(row.id);
        router.patch(`/purchases/${row.id}/paid`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/purchases/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    return (
        <AppShell
            title="Pembelian & harga"
            subtitle="Harga, hutang, dan pembayaran"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Pembelian & harga" />

            <div className="stat-grid">
                <StatCard label="Hutang supplier" value={summary.debt_total} money tone="danger" />
                <StatCard label="Pembelian belum dibayar" value={number(summary.debt_count)} hint="pembelian" />
                <StatCard
                    label="Menunggu harga"
                    value={number(summary.unpriced_count)}
                    tone={summary.unpriced_count > 0 ? 'accent' : 'default'}
                    hint="antrean owner"
                />
                <StatCard label="Dibayar bulan ini" value={summary.paid_this_month} money tone="success" />
            </div>

            {outlets.length > 0 ? (
                <Card title="Filter outlet" subtitle="Harga dan hutang dipisah per outlet">
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
                title="Antrean menunggu harga"
                subtitle="Manajer sudah menerima barang, owner belum mengisi harga"
            >
                {queue.length === 0 ? (
                    <EmptyState
                        title="Tidak ada antrean"
                        description="Semua penerimaan sudah dihargai."
                    />
                ) : (
                    <ul className="list">
                        {queue.map((row) => (
                            <li key={row.id} className="list__row">
                                <div className="list__main">
                                    <p className="list__title">{row.supplier ?? '-'}</p>
                                    <p className="muted">
                                        {row.outlet} · {dateId(row.received_at)} · {number(row.items)} item ·{' '}
                                        {number(row.qty)} unit
                                    </p>
                                </div>
                                <span className="row-actions">
                                    <Link href={`/purchases/${row.id}/price`} className="link">
                                        Isi harga
                                    </Link>
                                    <button
                                        type="button"
                                        className="link-danger"
                                        onClick={() => setPendingDelete(row)}
                                    >
                                        Hapus
                                    </button>
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Card title="Pembelian sudah dihargai" subtitle="Masuk hitungan hutang sampai ditandai dibayar">
                <DataTable
                    caption="Daftar pembelian yang sudah dihargai"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'supplier', label: 'Supplier' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'qty', label: 'Jumlah', align: 'right' },
                        { key: 'total', label: 'Total', align: 'right' },
                        { key: 'status', label: 'Status' },
                        { key: 'action', label: 'Aksi' },
                    ]}
                    rows={purchases.data}
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
                            case 'qty':
                                return number(row.qty);
                            case 'total':
                                return <span className="money">{rupiah(row.total)}</span>;
                            case 'status':
                                return row.is_paid ? (
                                    <Badge tone="success">Lunas {dateId(row.paid_at)}</Badge>
                                ) : (
                                    <Badge tone="danger">Belum dibayar</Badge>
                                );
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <Link href={`/purchases/${row.id}/price`} className="link">
                                            Ubah harga
                                        </Link>
                                        {!row.is_paid ? (
                                            <button
                                                type="button"
                                                className="link"
                                                disabled={busyId === row.id}
                                                onClick={() => markPaid(row)}
                                            >
                                                Tandai dibayar
                                            </button>
                                        ) : null}
                                    </span>
                                );
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada pembelian berharga"
                            description="Lengkapi harga pada antrean di atas."
                        />
                    }
                />

                <Pagination meta={purchases} />
            </Card>

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Batalkan pembelian"
                message="Pembelian berikut dibatalkan dan stok dikembalikan sebesar jumlah item:"
                itemName={
                    pendingDelete
                        ? `${pendingDelete.supplier ?? '-'} · ${dateId(pendingDelete.received_at)} · ${number(pendingDelete.qty)} unit`
                        : null
                }
                confirmLabel="Batalkan"
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
