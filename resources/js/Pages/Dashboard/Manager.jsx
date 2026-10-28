import { Head, Link, router, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateId, number } from '../../utils/format';

export default function Manager({ summary, belowMin, recentReceipts }) {
    const { auth } = usePage().props;

    return (
        <AppShell
            title="Dashboard stok"
            subtitle={summary.outlet ? `${summary.outlet.name} (${summary.outlet.code})` : 'Outlet belum ditugaskan'}
            badge={<Badge>{auth.user.role_label}</Badge>}
            reminder={summary.opname_due}
            reminderTitle="Stock opname minggu ini belum dilakukan."
            reminderDescription={`Lakukan sebelum Minggu, ${dateId(summary.week_end)}.`}
            actions={
                <Button variant="outline" onClick={() => router.post('/logout')}>
                    Keluar
                </Button>
            }
        >
            <Head title="Dashboard stok" />

            <div className="stat-grid">
                <StatCard label="Produk aktif" value={number(summary.product_count)} hint="produk" />
                <StatCard
                    label="Di bawah minimum"
                    value={number(summary.below_min_count)}
                    tone={summary.below_min_count > 0 ? 'accent' : 'default'}
                    hint="perlu restock"
                />
                <StatCard label="Penerimaan" value={number(summary.receipts_this_week)} hint="minggu ini" />
                <StatCard
                    label="Opname minggu ini"
                    value={summary.opname_due ? 'Belum' : 'Sudah'}
                    tone={summary.opname_due ? 'accent' : 'success'}
                    hint={`${dateId(summary.week_start)} – ${dateId(summary.week_end)}`}
                />
            </div>

            <Card
                title="Produk di bawah minimum"
                subtitle="Stok hitung dari ledger, bukan kolom saldo"
                action={summary.below_min_count > 0 ? <Link href="/products">Lihat stok</Link> : null}
            >
                {belowMin.length === 0 ? (
                    <EmptyState title="Semua stok aman" description="Tidak ada produk di bawah minimum di outlet ini." />
                ) : (
                    <ul className="list">
                        {belowMin.map((product) => (
                            <li key={product.id} className="list__row">
                                <div className="list__main">
                                    <p className="list__title">{product.name}</p>
                                    <p className="muted">
                                        {product.sku} · satuan {product.unit}
                                    </p>
                                </div>
                                <div className="list__value">
                                    <strong className="is-warn">{number(product.stock)}</strong>
                                    <span className="muted">/ min {number(product.min_stock)}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Card
                title="Penerimaan terakhir"
                subtitle="Tanpa harga dan tanpa total"
                action={<Link href="/receipts">Riwayat</Link>}
            >
                <DataTable
                    caption="Penerimaan terakhir outlet sendiri"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'supplier', label: 'Supplier' },
                        { key: 'items', label: 'Item', align: 'right' },
                        { key: 'qty', label: 'Jumlah', align: 'right' },
                        { key: 'status', label: 'Status' },
                    ]}
                    rows={recentReceipts}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'date':
                                return dateId(row.received_at);
                            case 'supplier':
                                return row.supplier;
                            case 'items':
                                return number(row.items);
                            case 'qty':
                                return number(row.qty);
                            case 'status':
                                return row.is_priced ? (
                                    <Badge tone="success">Sudah dihargai</Badge>
                                ) : (
                                    <Badge tone="accent">Menunggu harga</Badge>
                                );
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada penerimaan"
                            description="Catat barang datang lewat tombol Terima."
                            action={
                                <Button onClick={() => router.visit('/receipts/create')}>Terima barang</Button>
                            }
                        />
                    }
                />
            </Card>

            <Card title="Stock opname" subtitle="Wajib sekali setiap minggu">
                <ul className="list">
                    <li className="list__row">
                        <span className="muted">Periode berjalan</span>
                        <span>
                            {dateId(summary.week_start)} – {dateId(summary.week_end)}
                        </span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Status minggu ini</span>
                        {summary.opname_due ? (
                            <Badge tone="accent">Belum dilakukan</Badge>
                        ) : (
                            <Badge tone="success">Sudah dilakukan</Badge>
                        )}
                    </li>
                    <li className="list__row">
                        <span className="muted">Opname terakhir</span>
                        <span>{summary.last_opname_week ? dateId(summary.last_opname_week) : 'Belum pernah'}</span>
                    </li>
                </ul>
            </Card>
        </AppShell>
    );
}
