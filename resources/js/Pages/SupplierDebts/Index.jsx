import { Head, Link, router, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import EmptyState from '../../Components/EmptyState';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { dateId, number, rupiah } from '../../utils/format';

/**
 * Detail hutang supplier: barang yang sudah diterima tetapi belum dibayar.
 * Dikelompokkan per supplier, lengkap dengan daftar barang dan total.
 */
export default function Index({ suppliers, summary, outlets, filters }) {
    const { auth } = usePage().props;

    function applyOutlet(value) {
        router.get(
            '/supplier-debts',
            value ? { outlet: value } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function tandaiDibayar(purchase) {
        router.patch(`/purchases/${purchase.id}/paid`, {}, { preserveScroll: true });
    }

    return (
        <AppShell
            title="Hutang Supplier"
            subtitle="Barang yang sudah diterima tetapi belum dibayar"
            badge={<Badge>{auth.user.role_label}</Badge>}
            backHref="/dashboard"
        >
            <Head title="Hutang Supplier" />

            <div className="stat-grid">
                <StatCard label="Total hutang" value={summary.total} money tone="danger" />
                <StatCard label="Pembelian belum dibayar" value={number(summary.purchases)} hint="pembelian" />
                <StatCard label="Supplier" value={number(summary.suppliers)} hint="harus dibayar" />
                <StatCard label="Jumlah barang" value={number(summary.quantity)} hint="unit diterima" />
            </div>

            {outlets.length > 0 ? (
                <Card title="Filter outlet" subtitle="Hutang dipisah per outlet">
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
                                    {outlet.name} ({outlet.code})
                                </option>
                            ))}
                        </select>
                    </label>
                </Card>
            ) : null}

            {suppliers.length === 0 ? (
                <Card title="Tidak ada hutang" subtitle="Semua pembelian sudah dibayar">
                    <EmptyState
                        title="Tidak ada hutang supplier"
                        description="Semua pembelian yang sudah dihargai juga sudah ditandai dibayar."
                        action={
                            <Link href="/purchases" className="btn btn--outline">
                                Buka pembelian &amp; harga
                            </Link>
                        }
                    />
                </Card>
            ) : (
                suppliers.map((supplier) => (
                    <Card
                        key={supplier.supplier_id}
                        title={supplier.supplier}
                        subtitle={[
                            supplier.contact_name,
                            supplier.phone,
                            supplier.term_of_payment !== null ? `TOP ${number(supplier.term_of_payment)} hari` : null,
                            supplier.lead_time_days !== null ? `lead time ${number(supplier.lead_time_days)} hari` : null,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                        action={<span className="money">{rupiah(supplier.total)}</span>}
                    >
                        <p className="muted">
                            {number(supplier.purchases_count)} pembelian · {number(supplier.quantity)} unit belum dibayar
                        </p>

                        {supplier.purchases.map((purchase) => (
                            <div className="debt" key={purchase.id}>
                                <div className="debt__head">
                                    <div>
                                        <p className="list__title">
                                            {purchase.invoice_no ?? `Pembelian #${purchase.id}`}
                                        </p>
                                        <p className="muted">
                                            {dateId(purchase.received_at)} · {purchase.outlet} ({purchase.outlet_code})
                                        </p>
                                    </div>
                                    <span className="money">{rupiah(purchase.total)}</span>
                                </div>

                                <ul className="list">
                                    {purchase.items.map((item, index) => (
                                        <li className="list__row" key={`${purchase.id}-${index}`}>
                                            <div className="list__main">
                                                <p className="list__title">{item.product}</p>
                                                <p className="muted">
                                                    {item.sku} · {number(item.quantity)} {item.unit}
                                                    {item.price !== null ? ` × ${rupiah(item.price)}` : ''}
                                                </p>
                                            </div>
                                            <span className="money">{rupiah(item.subtotal)}</span>
                                        </li>
                                    ))}
                                </ul>

                                <div className="actions">
                                    <Button onClick={() => tandaiDibayar(purchase)}>Tandai dibayar</Button>
                                    <Link href={`/receipts/${purchase.id}`} className="btn btn--outline">
                                        Lihat penerimaan
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </Card>
                ))
            )}
        </AppShell>
    );
}
