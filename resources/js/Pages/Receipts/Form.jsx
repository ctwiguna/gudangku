import { Head, Link, useForm } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import DateInput from '../../Components/DateInput';
import FormField from '../../Components/FormField';
import SelectInput from '../../Components/SelectInput';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';

export default function Form({ receipt, suppliers, products, outlets, outletName, today }) {
    const editing = receipt !== null;

    const form = useForm({
        outlet_id: receipt?.outlet_id ? String(receipt.outlet_id) : '',
        supplier_id: receipt?.supplier_id ? String(receipt.supplier_id) : '',
        received_at: receipt?.received_at ?? today,
        invoice_no: receipt?.invoice_no ?? '',
        note: receipt?.note ?? '',
        items:
            receipt?.items?.length > 0
                ? receipt.items.map((item) => ({
                      product_id: String(item.product_id),
                      quantity: String(item.quantity),
                  }))
                : [{ product_id: '', quantity: '' }],
    });

    const { data, setData, errors, processing } = form;
    const errorList = Object.values(errors).filter((message) => typeof message === 'string');
    const isOwner = outlets.length > 0;

    function setItem(index, key, value) {
        const items = data.items.map((item, position) =>
            position === index ? { ...item, [key]: value } : item,
        );

        setData('items', items);
    }

    function addItem() {
        setData('items', [...data.items, { product_id: '', quantity: '' }]);
    }

    function removeItem(index) {
        setData(
            'items',
            data.items.filter((_, position) => position !== index),
        );
    }

    function submit(event) {
        event.preventDefault();

        if (editing) {
            form.put(`/receipts/${receipt.id}`);

            return;
        }

        form.post('/receipts');
    }

    return (
        <AppShell
            title={editing ? 'Ubah penerimaan' : 'Terima barang'}
            subtitle={isOwner ? 'Penerimaan dicatat tanpa harga' : `Outlet ${outletName ?? '-'}`}
            badge={<Badge>{isOwner ? 'Owner' : 'Manajer outlet'}</Badge>}
            backHref="/receipts"
        >
            <Head title={editing ? 'Ubah penerimaan' : 'Terima barang'} />

            {errorList.length > 0 ? (
                <div className="form-summary" role="alert">
                    <p className="form-summary__title">Periksa kembali isian berikut:</p>
                    <ul>
                        {errorList.map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {errors.items && typeof errors.items === 'string' ? (
                <div className="form-summary" role="alert">
                    {errors.items}
                </div>
            ) : null}

            <Card
                title="Data penerimaan"
                subtitle="Isi jumlah saja. Harga diisi owner setelah barang diterima."
            >
                <form onSubmit={submit} noValidate>
                    <div className="stack">
                        {isOwner ? (
                            <FormField label="Outlet" htmlFor="outlet_id" error={errors.outlet_id} required>
                                <SelectInput
                                    id="outlet_id"
                                    value={data.outlet_id}
                                    placeholder="Pilih outlet"
                                    options={outlets.map((outlet) => ({
                                        value: String(outlet.id),
                                        label: `${outlet.name} (${outlet.code})`,
                                    }))}
                                    error={Boolean(errors.outlet_id)}
                                    onChange={(event) => setData('outlet_id', event.target.value)}
                                />
                            </FormField>
                        ) : (
                            <FormField label="Outlet" htmlFor="outlet_name">
                                <TextInput id="outlet_name" value={outletName ?? '-'} readOnly />
                            </FormField>
                        )}

                        <FormField label="Supplier" htmlFor="supplier_id" error={errors.supplier_id} required>
                            <SelectInput
                                id="supplier_id"
                                value={data.supplier_id}
                                placeholder="Pilih supplier"
                                options={suppliers.map((supplier) => ({
                                    value: String(supplier.id),
                                    label: supplier.name,
                                }))}
                                error={Boolean(errors.supplier_id)}
                                onChange={(event) => setData('supplier_id', event.target.value)}
                            />
                        </FormField>

                        <div className="grid grid--2">
                            <FormField label="Tanggal terima" htmlFor="received_at" error={errors.received_at} required>
                                <DateInput
                                    id="received_at"
                                    value={data.received_at}
                                    error={Boolean(errors.received_at)}
                                    onChange={(event) => setData('received_at', event.target.value)}
                                />
                            </FormField>

                            <FormField
                                label="Nomor invoice"
                                htmlFor="invoice_no"
                                error={errors.invoice_no}
                                hint="Opsional."
                            >
                                <TextInput
                                    id="invoice_no"
                                    value={data.invoice_no}
                                    onChange={(event) => setData('invoice_no', event.target.value)}
                                />
                            </FormField>
                        </div>
                    </div>

                    <div className="items">
                        <div className="items__head">
                            <h3 className="items__title">Barang datang</h3>
                            <Button type="button" variant="outline" onClick={addItem}>
                                Tambah barang
                            </Button>
                        </div>

                        {data.items.map((item, index) => (
                            <div className="items__row" key={`item-${index}`}>
                                <FormField
                                    label="Produk"
                                    htmlFor={`product-${index}`}
                                    error={errors[`items.${index}.product_id`]}
                                >
                                    <SelectInput
                                        id={`product-${index}`}
                                        value={item.product_id}
                                        placeholder="Pilih produk"
                                        options={products.map((product) => ({
                                            value: String(product.id),
                                            label: `${product.name} (${product.unit})`,
                                        }))}
                                        error={Boolean(errors[`items.${index}.product_id`])}
                                        onChange={(event) => setItem(index, 'product_id', event.target.value)}
                                    />
                                </FormField>

                                <FormField
                                    label="Jumlah"
                                    htmlFor={`quantity-${index}`}
                                    error={errors[`items.${index}.quantity`]}
                                >
                                    <TextInput
                                        id={`quantity-${index}`}
                                        type="number"
                                        inputMode="numeric"
                                        min="1"
                                        value={item.quantity}
                                        error={Boolean(errors[`items.${index}.quantity`])}
                                        onChange={(event) => setItem(index, 'quantity', event.target.value)}
                                    />
                                </FormField>

                                <div className="items__remove">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => removeItem(index)}
                                        disabled={data.items.length === 1}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="stack">
                        <FormField label="Catatan" htmlFor="note" error={errors.note}>
                            <TextInput
                                id="note"
                                value={data.note}
                                placeholder="Contoh: barang datang pagi"
                                onChange={(event) => setData('note', event.target.value)}
                            />
                        </FormField>

                        <div className="actions">
                            <Button type="submit" processing={processing} processingLabel="Menyimpan">
                                {editing ? 'Simpan perubahan' : 'Simpan penerimaan'}
                            </Button>
                            <Link href="/receipts" className="btn btn--outline">
                                Batal
                            </Link>
                        </div>
                    </div>
                </form>
            </Card>
        </AppShell>
    );
}
