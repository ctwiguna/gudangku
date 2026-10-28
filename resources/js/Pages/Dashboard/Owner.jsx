import { Head, Link, router, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateId, monthLabel, number, rupiah } from '../../utils/format';

export default function Owner({ summary, outlets, belowMin, recentReceipts, recentExpenses }) {
    const { auth } = usePage().props;

    return (
        <AppShell
            title="Dashboard owner"
            subtitle={`${auth.user.name} · ${monthLabel(summary.month)}`}
            badge={<Badge>{auth.user.role_label}</Badge>}
            actions={
                <Button variant="outline" onClick={() => router.post('/logout')}>
                    Keluar
                </Button>
            }
        >
            <Head title="Dashboard owner" />

            <div className="stat-grid">
                <StatCard
                    label="Pengeluaran bulan ini"
                    value={summary.expenses_this_month}
                    money
                    tone="accent"
                    hint={`${summary.outlet_count} outlet`}
                />
                <StatCard
                    label="Hutang supplier"
                    value={summary.debt_total}
                    money
                    tone="danger"
                    hint={`${summary.debt_count} pembelian belum dibayar`}
                />
                <StatCard
                    label="Menunggu harga"
                    value={summary.unpriced_count}
                    hint="pembelian"
                />
                <StatCard label="Nilai stok" value={summary.stock_value} money />
                <StatCard
                    label="Produk di bawah minimum"
                    value={summary.below_min_count}
                    tone={summary.below_min_count > 0 ? 'accent' : 'default'}
                    hint="seluruh outlet"
                />
                <StatCard
                    label="Opname minggu ini"
                    value={`${summary.opname_done_count}/${summary.outlet_count}`}
                    tone={summary.opname_done_count < summary.outlet_count ? 'accent' : 'success'}
                    hint="outlet sudah opname"
                />
            </div>

            <Card title="Status opname per outlet" subtitle="Minggu berjalan, Senin sampai Minggu">
                <DataTable
                    caption="Status opname per outlet"
                    columns={[
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'opname', label: 'Opname' },
                        { key: 'stock', label: 'Nilai stok', align: 'right' },
                        { key: 'min', label: 'Di bawah min', align: 'right' },
                        { key: 'expense', label: 'Pengeluaran', align: 'right' },
                        { key: 'debt', label: 'Hutang', align: 'right' },
                    ]}
                    rows={outlets}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'outlet':
                                return (
                                    <span>
                                        {row.name}
                                        <br />
                                        <span className="muted">{row.code}</span>
                                    </span>
                                );
                            case 'opname':
                                return row.opname_done ? (
                                    <Badge tone="success">Sudah</Badge>
                                ) : (
                                    <Badge tone="accent">Belum</Badge>
                                );
                            case 'stock':
                                return <span className="money">{rupiah(row.stock_value)}</span>;
                            case 'min':
                                return <span className={row.below_min_count > 0 ? 'is-warn' : ''}>{number(row.below_min_count)}</span>;
                            case 'expense':
                                return <span className="money">{rupiah(row.expenses_this_month)}</span>;
                            case 'debt':
                                return <span className="money">{rupiah(row.debt)}</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={
                        <EmptyState
                            title="Belum ada outlet"
                            description="Tambahkan outlet lebih dulu di menu Pengaturan."
                        />
                    }
                />
            </Card>

            <div className="grid grid--2">
                <Card title="Produk di bawah minimum" subtitle="Perlu restock segera">
                    {belowMin.length === 0 ? (
                        <EmptyState title="Semua stok aman" description="Tidak ada produk di bawah minimum." />
                    ) : (
                        <ul className="list">
                            {belowMin.map((product) => (
                                <li key={`${product.id}-${product.outlet}`} className="list__row">
                                    <div className="list__main">
                                        <p className="list__title">{product.name}</p>
                                        <p className="muted">
                                            {product.outlet} · {product.sku}
                                        </p>
                                    </div>
                                    <div className="list__value">
                                        <strong className="is-warn">{product.stock}</strong>
                                        <span className="muted">/ min {product.min_stock}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Pengeluaran per kategori" subtitle={monthLabel(summary.month)}>
                    {summary.expenses_by_category.length === 0 ? (
                        <EmptyState title="Belum ada pengeluaran" description="Catat pengeluaran bulan ini." />
                    ) : (
                        <ul className="list">
                            {summary.expenses_by_category.map((row) => (
                                <li key={row.category} className="list__row">
                                    <p className="list__title">{row.label}</p>
                                    <span className="money">{rupiah(row.total)}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <Card
                title="Antrean pembelian menunggu harga"
                subtitle="Manajer sudah menerima barang, owner belum mengisi harga"
                action={<Link href="/purchases">Lihat semua</Link>}
            >
                {recentReceipts.filter((row) => !row.is_priced).length === 0 ? (
                    <EmptyState title="Tidak ada antrean" description="Semua penerimaan sudah dihargai." />
                ) : (
                    <ul className="list">
                        {recentReceipts
                            .filter((row) => !row.is_priced)
                            .map((row) => (
                                <li key={row.id} className="list__row">
                                    <div className="list__main">
                                        <p className="list__title">{row.supplier}</p>
                                        <p className="muted">
                                            {row.outlet} · {dateId(row.received_at)}
                                        </p>
                                    </div>
                                    <Badge tone="accent">Menunggu harga</Badge>
                                </li>
                            ))}
                    </ul>
                )}
            </Card>

            <Card title="Penerimaan terakhir">
                <DataTable
                    caption="Penerimaan terakhir"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'supplier', label: 'Supplier' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'status', label: 'Status' },
                        { key: 'total', label: 'Total', align: 'right' },
                    ]}
                    rows={recentReceipts}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'date':
                                return dateId(row.received_at);
                            case 'supplier':
                                return row.supplier;
                            case 'outlet':
                                return row.outlet;
                            case 'status':
                                return row.is_paid ? (
                                    <Badge tone="success">Lunas</Badge>
                                ) : row.is_priced ? (
                                    <Badge tone="danger">Belum dibayar</Badge>
                                ) : (
                                    <Badge tone="accent">Menunggu harga</Badge>
                                );
                            case 'total':
                                return <span className="money">{rupiah(row.total)}</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={<EmptyState title="Belum ada penerimaan" description="Penerimaan barang dicatat oleh manajer." />}
                />
            </Card>

            <Card title="Pengeluaran terakhir">
                <DataTable
                    caption="Pengeluaran terakhir"
                    columns={[
                        { key: 'date', label: 'Tanggal' },
                        { key: 'category', label: 'Kategori' },
                        { key: 'outlet', label: 'Outlet' },
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
                            case 'amount':
                                return <span className="money">{rupiah(row.amount)}</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={<EmptyState title="Belum ada pengeluaran" />}
                />
            </Card>
        </AppShell>
    );
}
