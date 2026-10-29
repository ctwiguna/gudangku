import { Head, Link, useForm } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import CurrencyInput from '../../Components/CurrencyInput';
import FormField from '../../Components/FormField';
import SelectInput from '../../Components/SelectInput';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';

export default function Form({ product, categories, suppliers }) {
    const editing = product !== null;

    const form = useForm({
        category_id: product?.category_id ? String(product.category_id) : '',
        supplier_id: product?.supplier_id ? String(product.supplier_id) : '',
        name: product?.name ?? '',
        sku: product?.sku ?? '',
        unit: product?.unit ?? 'pcs',
        min_stock: product?.min_stock ?? 0,
        price_buy: product?.price_buy ?? '',
        is_active: product?.is_active ?? true,
    });

    const { data, setData, errors, processing } = form;
    const errorList = Object.values(errors);

    function submit(event) {
        event.preventDefault();

        if (editing) {
            form.put(`/products/${product.id}`);

            return;
        }

        form.post('/products');
    }

    return (
        <AppShell
            title={editing ? 'Ubah barang' : 'Tambah barang'}
            subtitle="Harga per pcs hanya untuk owner"
            badge={<Badge>Owner</Badge>}
            backHref="/products"
        >
            <Head title={editing ? 'Ubah barang' : 'Tambah barang'} />

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

            <Card>
                <form onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama barang" htmlFor="name" error={errors.name} required>
                            <TextInput
                                id="name"
                                value={data.name}
                                placeholder="Contoh: Gas LPG 12kg"
                                error={Boolean(errors.name)}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </FormField>

                        <div className="grid grid--2">
                            <FormField label="Kode SKU" htmlFor="sku" error={errors.sku} required>
                                <TextInput
                                    id="sku"
                                    value={data.sku}
                                    placeholder="PLK-KCL"
                                    error={Boolean(errors.sku)}
                                    onChange={(event) => setData('sku', event.target.value)}
                                />
                            </FormField>

                            <FormField
                                label="Satuan"
                                htmlFor="unit"
                                error={errors.unit}
                                hint="Bawaan pcs. Ubah hanya kalau satuannya berbeda."
                                required
                            >
                                <TextInput
                                    id="unit"
                                    value={data.unit}
                                    error={Boolean(errors.unit)}
                                    onChange={(event) => setData('unit', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <FormField label="Kategori" htmlFor="category_id" error={errors.category_id} required>
                            <SelectInput
                                id="category_id"
                                value={data.category_id}
                                placeholder="Pilih kategori"
                                options={categories.map((category) => ({
                                    value: String(category.id),
                                    label: category.name,
                                }))}
                                error={Boolean(errors.category_id)}
                                onChange={(event) => setData('category_id', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Supplier utama"
                            htmlFor="supplier_id"
                            error={errors.supplier_id}
                            hint="Opsional. Supplier bisa dikosongkan."
                        >
                            <SelectInput
                                id="supplier_id"
                                value={data.supplier_id}
                                placeholder="Tanpa supplier"
                                options={suppliers.map((supplier) => ({
                                    value: String(supplier.id),
                                    label: supplier.name,
                                }))}
                                error={Boolean(errors.supplier_id)}
                                onChange={(event) => setData('supplier_id', event.target.value)}
                            />
                        </FormField>

                        <div className="grid grid--2">
                            <FormField
                                label="Stok minimum"
                                htmlFor="min_stock"
                                error={errors.min_stock}
                                hint="Dipakai untuk peringatan restock."
                                required
                            >
                                <TextInput
                                    id="min_stock"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    value={data.min_stock}
                                    error={Boolean(errors.min_stock)}
                                    onChange={(event) => setData('min_stock', event.target.value)}
                                />
                            </FormField>

                            <FormField
                                label="Harga beli per pcs"
                                htmlFor="price_buy"
                                error={errors.price_buy}
                                hint="Harga acuan terakhir, bukan per outlet."
                                required
                            >
                                <CurrencyInput
                                    id="price_buy"
                                    value={data.price_buy}
                                    placeholder="0"
                                    error={Boolean(errors.price_buy)}
                                    onChange={(value) => setData('price_buy', value)}
                                />
                            </FormField>
                        </div>

                        <label className="checkbox">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(event) => setData('is_active', event.target.checked)}
                            />
                            <span>Aktifkan produk ini di form penerimaan dan opname</span>
                        </label>

                        <div className="actions">
                            <Button type="submit" processing={processing} processingLabel="Menyimpan">
                                {editing ? 'Simpan perubahan' : 'Simpan'}
                            </Button>
                            <Link href="/products" className="btn btn--outline">
                                Batal
                            </Link>
                        </div>
                    </div>
                </form>
            </Card>
        </AppShell>
    );
}
