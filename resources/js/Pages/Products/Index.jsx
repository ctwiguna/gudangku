import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import ConfirmDialog from '../../Components/ConfirmDialog';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { number, rupiah } from '../../utils/format';

export default function Index({ products, canManage, outletLabel, filters, outlets }) {
    const { auth } = usePage().props;
    const [pendingDelete, setPendingDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState(null);

    const belowMin = products.filter((product) => product.below_min);
    const activeCount = products.filter((product) => product.is_active).length;
    const stockValue = products.reduce(
        (total, product) => total + Number(product.price_buy ?? 0) * Number(product.stock ?? 0),
        0,
    );

    function applyOutlet(value) {
        router.get('/products', value ? { outlet: value } : {}, { preserveState: true, preserveScroll: true, replace: true });
    }

    function confirmDelete() {
        if (!pendingDelete) {
            return;
        }

        setDeleting(true);
        router.delete(`/products/${pendingDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    }

    function toggleStatus(product) {
        setBusyId(product.id);
        router.patch(
            `/products/${product.id}/status`,
            {},
            { preserveScroll: true, onFinish: () => setBusyId(null) },
        );
    }

    const columns = [
        { key: 'product', label: 'Produk' },
        { key: 'category', label: 'Kategori' },
        { key: 'supplier', label: 'Supplier' },
        { key: 'stock', label: 'Stok', align: 'right' },
        { key: 'min', label: 'Min', align: 'right' },
    ];

    if (canManage) {
        columns.push({ key: 'price', label: 'Harga beli', align: 'right' });
    }

    columns.push({ key: 'status', label: 'Status' });

    if (canManage) {
        columns.push({ key: 'action', label: 'Aksi' });
    }

    return (
        <AppShell
            title={canManage ? 'Produk' : 'Stok'}
            subtitle={outletLabel}
            badge={<Badge>{auth.user.role_label}</Badge>}
            actions={
                canManage ? (
                    <Link href="/products/create" className="btn">
                        Tambah produk
                    </Link>
                ) : null
            }
        >
            <Head title={canManage ? 'Produk' : 'Stok'} />

            <div className="stat-grid">
                <StatCard label="Jumlah produk" value={number(products.length)} hint="terdaftar" />
                <StatCard label="Produk aktif" value={number(activeCount)} hint="muncul di form baru" />
                <StatCard
                    label="Di bawah minimum"
                    value={number(belowMin.length)}
                    tone={belowMin.length > 0 ? 'accent' : 'default'}
                    hint="perlu restock"
                />
                {canManage ? (
                    <StatCard label="Nilai stok" value={stockValue} money />
                ) : (
                    <StatCard label="Satuan" value="pcs" hint="satuan stok outlet" />
                )}
            </div>

            {canManage && outlets.length > 0 ? (
                <Card title="Filter outlet" subtitle="Owner bisa melihat semua outlet atau satu outlet">
                    <label className="field">
                        <span className="field__label">Outlet</span>
                        <select
                            className="field__input"
                            value={filters.outlet ? String(filters.outlet) : ''}
                            onChange={(event) => applyOutlet(event.target.value)}
                        >
                            <option value="">Semua outlet (saldo gabungan)</option>
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
                title="Daftar produk"
                subtitle={
                    canManage
                        ? 'Saldo stok dihitung dari ledger pergerakan'
                        : 'Tanpa harga, sesuai pembatasan akses manajer'
                }
            >
                <DataTable
                    caption="Daftar produk dan saldo stok"
                    columns={columns}
                    rows={products}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'product':
                                return (
                                    <span>
                                        {row.name}
                                        <br />
                                        <span className="muted">
                                            {row.sku} · {row.unit}
                                        </span>
                                    </span>
                                );
                            case 'category':
                                return row.category ?? <span className="muted">-</span>;
                            case 'supplier':
                                return row.supplier ?? <span className="muted">-</span>;
                            case 'stock':
                                return (
                                    <span className={row.below_min ? 'is-warn' : ''}>{number(row.stock)}</span>
                                );
                            case 'min':
                                return number(row.min_stock);
                            case 'price':
                                return <span className="money">{rupiah(row.price_buy)}</span>;
                            case 'status':
                                return row.is_active ? (
                                    <Badge tone="success">Aktif</Badge>
                                ) : (
                                    <Badge tone="muted">Nonaktif</Badge>
                                );
                            case 'action':
                                return (
                                    <span className="row-actions">
                                        <Link href={`/products/${row.id}/edit`}>Ubah</Link>
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
                            title="Belum ada produk"
                            description="Tambahkan produk lebih dulu sebelum mencatat penerimaan barang."
                            action={
                                canManage ? (
                                    <Link href="/products/create" className="btn">
                                        Tambah produk
                                    </Link>
                                ) : null
                            }
                        />
                    }
                />
            </Card>

            {belowMin.length > 0 ? (
                <Card title="Perlu restock" subtitle="Stok di bawah minimum">
                    <ul className="list">
                        {belowMin.map((product) => (
                            <li key={product.id} className="list__row">
                                <div className="list__main">
                                    <p className="list__title">{product.name}</p>
                                    <p className="muted">{product.sku}</p>
                                </div>
                                <div className="list__value">
                                    <strong className="is-warn">{number(product.stock)}</strong>
                                    <span className="muted">/ min {number(product.min_stock)}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                </Card>
            ) : null}

            <ConfirmDialog
                open={pendingDelete !== null}
                title="Hapus produk"
                message="Produk berikut akan dihapus permanen."
                itemName={pendingDelete ? `${pendingDelete.name} (${pendingDelete.sku})` : null}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setPendingDelete(null)}
            />
        </AppShell>
    );
}
