import { Head, Link, useForm } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import CurrencyInput from '../../Components/CurrencyInput';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';
import { dateId, number, rupiah } from '../../utils/format';

export default function Price({ purchase }) {
    const form = useForm({
        invoice_no: purchase.invoice_no ?? '',
        note: purchase.note ?? '',
        items: purchase.items.map((item) => ({
            id: item.id,
            price: item.price === '' ? '' : Number(item.price),
        })),
    });

    const { data, setData, errors, processing } = form;

    const total = purchase.items.reduce((sum, item, index) => {
        const price = Number(data.items[index]?.price ?? 0);

        return sum + price * Number(item.quantity);
    }, 0);

    const filledCount = data.items.filter((item) => item.price !== '' && item.price !== null).length;
    const allFilled = filledCount === data.items.length;

    function setPrice(index, value) {
        setData(
            'items',
            data.items.map((item, position) => (position === index ? { ...item, price: value } : item)),
        );
    }

    function submit(event) {
        event.preventDefault();
        form.put(`/purchases/${purchase.id}/price`);
    }

    return (
        <AppShell
            title="Isi harga pembelian"
            subtitle={`${purchase.supplier ?? '-'} · ${purchase.outlet ?? '-'} · ${dateId(purchase.received_at)}`}
            badge={
                purchase.is_priced ? (
                    <Badge tone="success">Sudah dihargai</Badge>
                ) : (
                    <Badge tone="accent">Menunggu harga</Badge>
                )
            }
            backHref="/purchases"
        >
            <Head title="Isi harga pembelian" />

            <Card title="Ringkasan penerimaan">
                <ul className="list">
                    <li className="list__row">
                        <span className="muted">Outlet</span>
                        <span>
                            {purchase.outlet} {purchase.outlet_code ? `(${purchase.outlet_code})` : ''}
                        </span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Tanggal terima</span>
                        <span>{dateId(purchase.received_at)}</span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Status</span>
                        <span>
                            {purchase.is_paid
                                ? `Lunas ${dateId(purchase.paid_at)}`
                                : purchase.is_priced
                                  ? 'Sudah dihargai, belum dibayar'
                                  : 'Belum dihargai'}
                        </span>
                    </li>
                </ul>
            </Card>

            <Card
                title="Harga per pcs"
                subtitle="Semua item harus diisi sekaligus agar masuk hitungan hutang"
            >
                {errors.items && typeof errors.items === 'string' ? (
                    <div className="form-summary" role="alert">
                        {errors.items}
                    </div>
                ) : null}

                <form onSubmit={submit} noValidate>
                    <div className="stack">
                        {purchase.items.map((item, index) => {
                            const price = Number(data.items[index]?.price ?? 0);
                            const subtotal = price * Number(item.quantity);

                            return (
                                <div className="items__row" key={item.id}>
                                    <div>
                                        <p className="list__title">{item.product}</p>
                                        <p className="muted">
                                            {item.sku} · {number(item.quantity)} {item.unit}
                                        </p>
                                    </div>

                                    <FormField
                                        label="Harga per pcs"
                                        htmlFor={`price-${item.id}`}
                                        error={errors[`items.${index}.price`]}
                                    >
                                        <CurrencyInput
                                            id={`price-${item.id}`}
                                            value={data.items[index]?.price ?? ''}
                                            placeholder="0"
                                            error={Boolean(errors[`items.${index}.price`])}
                                            onChange={(value) => setPrice(index, value)}
                                        />
                                    </FormField>

                                    <div className="list__value">
                                        <span className="muted">Subtotal</span>
                                        <strong className="money">{rupiah(subtotal)}</strong>
                                    </div>
                                </div>
                            );
                        })}

                        <div className="items__head">
                            <div>
                                <p className="muted">
                                    {filledCount} dari {purchase.items.length} item sudah diisi harga
                                </p>
                                <p className="list__title">
                                    Total: <span className="money">{rupiah(total)}</span>
                                </p>
                            </div>
                            {allFilled ? (
                                <Badge tone="success">Siap disimpan</Badge>
                            ) : (
                                <Badge tone="accent">Belum lengkap</Badge>
                            )}
                        </div>

                        <div className="grid grid--2">
                            <FormField label="Nomor invoice" htmlFor="invoice_no" error={errors.invoice_no}>
                                <TextInput
                                    id="invoice_no"
                                    value={data.invoice_no}
                                    onChange={(event) => setData('invoice_no', event.target.value)}
                                />
                            </FormField>

                            <FormField label="Catatan" htmlFor="note" error={errors.note}>
                                <TextInput
                                    id="note"
                                    value={data.note}
                                    onChange={(event) => setData('note', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <div className="actions">
                            <Button
                                type="submit"
                                processing={processing}
                                processingLabel="Menyimpan"
                                disabled={!allFilled}
                            >
                                Simpan harga
                            </Button>
                            <Link href="/purchases" className="btn btn--outline">
                                Batal
                            </Link>
                        </div>
                    </div>
                </form>
            </Card>
        </AppShell>
    );
}
