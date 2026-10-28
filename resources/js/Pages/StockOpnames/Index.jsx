import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DataTable from '../../Components/DataTable';
import EmptyState from '../../Components/EmptyState';
import FormField from '../../Components/FormField';
import ReminderBanner from '../../Components/ReminderBanner';
import StockCountRow from '../../Components/StockCountRow';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { dateId, dateTimeId, signedNumber } from '../../utils/format';

export default function Index({
    weekStart,
    weekEnd,
    outlet,
    products,
    existing,
    history,
    outlets,
    outletStatuses,
    filters,
}) {
    const { auth } = usePage().props;
    const isOwner = outlets.length > 0;

    const [counts, setCounts] = useState(() =>
        Object.fromEntries(products.map((product) => [product.id, String(product.system_qty)])),
    );
    const [notes, setNotes] = useState({});
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);

    const filledCount = useMemo(
        () => products.filter((product) => counts[product.id] !== '' && counts[product.id] !== undefined).length,
        [counts, products],
    );

    const allFilled = products.length > 0 && filledCount === products.length;

    function applyOutlet(value) {
        router.get(
            '/stock-opnames',
            value ? { outlet: value } : {},
            { preserveState: false, preserveScroll: true, replace: true },
        );
    }

    function submit(event) {
        event.preventDefault();

        const payload = {
            counts: products.map((product) => ({
                product_id: product.id,
                counted_qty: Number(counts[product.id] ?? 0),
                note: notes[product.id] || null,
            })),
            note: note || null,
        };

        if (isOwner) {
            payload.outlet_id = outlet?.id;
        }

        setProcessing(true);
        router.post('/stock-opnames', payload, { onFinish: () => setProcessing(false) });
    }

    return (
        <AppShell
            title="Stock opname"
            subtitle={`${dateId(weekStart)} – ${dateId(weekEnd)}`}
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Stock opname" />

            {isOwner ? (
                <Card title="Pilih outlet" subtitle="Owner bisa melakukan opname di outlet mana pun">
                    <label className="field">
                        <span className="field__label">Outlet</span>
                        <select
                            className="field__input"
                            value={outlet?.id ? String(outlet.id) : ''}
                            onChange={(event) => applyOutlet(event.target.value)}
                        >
                            {outlets.map((row) => (
                                <option key={row.id} value={row.id}>
                                    {row.name} ({row.code})
                                </option>
                            ))}
                        </select>
                    </label>
                </Card>
            ) : null}

            {existing === null ? (
                <ReminderBanner
                    title="Stock opname minggu ini belum dilakukan."
                    description={`Hitung stok fisik lalu simpan sebelum Minggu, ${dateId(weekEnd)}.`}
                />
            ) : (
                <div className="toast toast--success" role="status">
                    Opname minggu ini sudah selesai pada {dateTimeId(existing.completed_at)} oleh {existing.user}.
                </div>
            )}

            {existing === null ? (
                <Card
                    title={`Hitung fisik — ${outlet?.name ?? 'outlet'}`}
                    subtitle="Stok sistem dihitung ulang di server saat menyimpan"
                >
                    {products.length === 0 ? (
                        <EmptyState
                            title="Tidak ada produk aktif"
                            description="Tambahkan produk lebih dulu sebelum melakukan opname."
                        />
                    ) : (
                        <form onSubmit={submit}>
                            {products.map((product) => (
                                <StockCountRow
                                    key={product.id}
                                    product={product}
                                    systemQty={product.system_qty}
                                    counted={counts[product.id] ?? ''}
                                    note={notes[product.id] ?? ''}
                                    onCountChange={(value) =>
                                        setCounts((current) => ({ ...current, [product.id]: value }))
                                    }
                                    onNoteChange={(value) =>
                                        setNotes((current) => ({ ...current, [product.id]: value }))
                                    }
                                />
                            ))}

                            <div className="stack" style={{ marginTop: 'var(--space-4)' }}>
                                <FormField label="Catatan opname" htmlFor="note" hint="Opsional.">
                                    <TextInput
                                        id="note"
                                        value={note}
                                        placeholder="Contoh: opname rutin mingguan"
                                        onChange={(event) => setNote(event.target.value)}
                                    />
                                </FormField>

                                <div className="items__head">
                                    <p className="muted">
                                        {filledCount} dari {products.length} produk sudah diisi
                                    </p>
                                    <Button
                                        type="submit"
                                        processing={processing}
                                        processingLabel="Menyimpan"
                                        disabled={!allFilled}
                                    >
                                        Simpan opname
                                    </Button>
                                </div>
                            </div>
                        </form>
                    )}
                </Card>
            ) : (
                <Card title="Hasil opname minggu ini" subtitle="Selisih sudah disesuaikan ke stok">
                    <DataTable
                        caption="Hasil stock opname minggu ini"
                        columns={[
                            { key: 'product', label: 'Produk' },
                            { key: 'system', label: 'Stok sistem', align: 'right' },
                            { key: 'counted', label: 'Jumlah hitung', align: 'right' },
                            { key: 'difference', label: 'Selisih', align: 'right' },
                        ]}
                        rows={existing.items}
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
                                case 'system':
                                    return row.system_qty;
                                case 'counted':
                                    return row.counted_qty;
                                case 'difference':
                                    if (row.difference === 0) {
                                        return <span className="muted">0</span>;
                                    }

                                    return (
                                        <span className={row.difference > 0 ? 'is-ok' : 'is-warn'}>
                                            {signedNumber(row.difference)}
                                        </span>
                                    );
                                default:
                                    return null;
                            }
                        }}
                    />
                </Card>
            )}

            {isOwner && outletStatuses.length > 0 ? (
                <Card title="Kepatuhan opname per outlet" subtitle="Minggu berjalan">
                    <DataTable
                        caption="Status opname per outlet"
                        columns={[
                            { key: 'outlet', label: 'Outlet' },
                            { key: 'status', label: 'Minggu ini' },
                            { key: 'last', label: 'Opname terakhir' },
                        ]}
                        rows={outletStatuses}
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
                                case 'status':
                                    return row.opname_done ? (
                                        <Badge tone="success">Sudah</Badge>
                                    ) : (
                                        <Badge tone="accent">Belum</Badge>
                                    );
                                case 'last':
                                    return row.last_opname_week ? dateId(row.last_opname_week) : 'Belum pernah';
                                default:
                                    return null;
                            }
                        }}
                    />
                </Card>
            ) : null}

            <Card title="Riwayat opname" subtitle="Delapan minggu terakhir">
                <DataTable
                    caption="Riwayat stock opname"
                    columns={[
                        { key: 'week', label: 'Minggu' },
                        { key: 'outlet', label: 'Outlet' },
                        { key: 'user', label: 'Petugas' },
                        { key: 'items', label: 'Item', align: 'right' },
                        { key: 'completed', label: 'Selesai' },
                    ]}
                    rows={history}
                    renderCell={(row, column) => {
                        switch (column.key) {
                            case 'week':
                                return (
                                    <span>
                                        {dateId(row.week_start)} – {dateId(row.week_end)}
                                        {row.is_current_week ? (
                                            <>
                                                {' '}
                                                <Badge tone="primary">Minggu ini</Badge>
                                            </>
                                        ) : null}
                                    </span>
                                );
                            case 'outlet':
                                return row.outlet;
                            case 'user':
                                return row.user;
                            case 'items':
                                return row.items;
                            case 'completed':
                                return dateTimeId(row.completed_at);
                            default:
                                return null;
                        }
                    }}
                    empty={<EmptyState title="Belum ada riwayat opname" />}
                />
            </Card>
        </AppShell>
    );
}
