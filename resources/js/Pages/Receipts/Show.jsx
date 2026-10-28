import { Head, Link } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import AppShell from '../../Layouts/AppShell';
import { dateId, number, rupiah } from '../../utils/format';

export default function Show({ receipt, canSeePrice }) {
    const columns = [
        { key: 'product', label: 'Produk' },
        { key: 'quantity', label: 'Jumlah', align: 'right' },
    ];

    if (canSeePrice) {
        columns.push({ key: 'price', label: 'Harga per pcs', align: 'right' });
        columns.push({ key: 'subtotal', label: 'Subtotal', align: 'right' });
    }

    return (
        <AppShell
            title={`Penerimaan #${receipt.id}`}
            subtitle={`${dateId(receipt.received_at)} · ${receipt.supplier ?? '-'}`}
            badge={
                receipt.is_paid ? (
                    <Badge tone="success">Lunas</Badge>
                ) : receipt.is_priced ? (
                    <Badge tone="danger">Belum dibayar</Badge>
                ) : (
                    <Badge tone="accent">Menunggu harga</Badge>
                )
            }
            backHref="/receipts"
            actions={
                receipt.can_edit ? (
                    <Link href={`/receipts/${receipt.id}/edit`} className="btn btn--outline">
                        Ubah jumlah
                    </Link>
                ) : null
            }
        >
            <Head title={`Penerimaan #${receipt.id}`} />

            <Card title="Ringkasan">
                <ul className="list">
                    <li className="list__row">
                        <span className="muted">Outlet</span>
                        <span>
                            {receipt.outlet} {receipt.outlet_code ? `(${receipt.outlet_code})` : ''}
                        </span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Supplier</span>
                        <span>{receipt.supplier ?? '-'}</span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Tanggal terima</span>
                        <span>{dateId(receipt.received_at)}</span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Nomor invoice</span>
                        <span>{receipt.invoice_no ?? '-'}</span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Dicatat oleh</span>
                        <span>{receipt.created_by ?? '-'}</span>
                    </li>
                    {canSeePrice ? (
                        <>
                            <li className="list__row">
                                <span className="muted">Tanggal dihargai</span>
                                <span>{receipt.priced_at ? dateId(receipt.priced_at) : 'Belum dihargai'}</span>
                            </li>
                            <li className="list__row">
                                <span className="muted">Tanggal bayar</span>
                                <span>{receipt.paid_at ? dateId(receipt.paid_at) : 'Belum dibayar'}</span>
                            </li>
                            <li className="list__row">
                                <span className="muted">Total</span>
                                <strong className="money">{rupiah(receipt.total)}</strong>
                            </li>
                        </>
                    ) : (
                        <li className="list__row">
                            <span className="muted">Harga dan total</span>
                            <span className="muted">Hanya owner yang bisa melihat</span>
                        </li>
                    )}
                    {receipt.note ? (
                        <li className="list__row">
                            <span className="muted">Catatan</span>
                            <span>{receipt.note}</span>
                        </li>
                    ) : null}
                </ul>
            </Card>

            <Card
                title="Barang diterima"
                subtitle={canSeePrice ? 'Harga per pcs diisi owner' : 'Jumlah saja, tanpa harga'}
            >
                <DataTable
                    caption="Barang yang diterima"
                    columns={columns}
                    rows={receipt.items}
                    renderCell={(row, column) => {
                        switch (column.key) {
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
                            case 'quantity':
                                return number(row.quantity);
                            case 'price':
                                return row.price === null ? (
                                    <Badge tone="accent">Belum dihargai</Badge>
                                ) : (
                                    <span className="money">{rupiah(row.price)}</span>
                                );
                            case 'subtotal':
                                return <span className="money">{rupiah(row.subtotal)}</span>;
                            default:
                                return null;
                        }
                    }}
                    empty={<EmptyState title="Tidak ada item" />}
                />
            </Card>

            {!receipt.is_priced ? (
                <p className="muted">
                    Penerimaan ini belum dihargai, jadi belum masuk hitungan hutang supplier.
                    Stok sudah bertambah sejak tanggal terima.
                </p>
            ) : null}
        </AppShell>
    );
}
