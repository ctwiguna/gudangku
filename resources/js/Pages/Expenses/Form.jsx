import { Head, Link, useForm } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import CurrencyInput from '../../Components/CurrencyInput';
import DateInput from '../../Components/DateInput';
import FormField from '../../Components/FormField';
import SelectInput from '../../Components/SelectInput';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';

export default function Form({ expense, outlets, categories, today }) {
    const editing = expense !== null;

    const form = useForm({
        outlet_id: expense?.outlet_id ? String(expense.outlet_id) : '',
        category: expense?.category ?? '',
        expense_date: expense?.expense_date ?? today,
        period_start: expense?.period_start ?? '',
        period_end: expense?.period_end ?? '',
        amount: expense?.amount ?? '',
        note: expense?.note ?? '',
    });

    const { data, setData, errors, processing } = form;
    const needsPeriod = data.category === 'gaji';
    const errorList = Object.values(errors);

    function submit(event) {
        event.preventDefault();

        if (editing) {
            form.put(`/expenses/${expense.id}`);

            return;
        }

        form.post('/expenses');
    }

    return (
        <AppShell
            title={editing ? 'Ubah pengeluaran' : 'Catat pengeluaran'}
            subtitle="Data keuangan hanya untuk owner"
            badge={<Badge>Owner</Badge>}
            backHref="/expenses"
        >
            <Head title={editing ? 'Ubah pengeluaran' : 'Catat pengeluaran'} />

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

                        <FormField label="Kategori" htmlFor="category" error={errors.category} required>
                            <SelectInput
                                id="category"
                                value={data.category}
                                placeholder="Pilih kategori"
                                options={categories}
                                error={Boolean(errors.category)}
                                onChange={(event) => setData('category', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Tanggal" htmlFor="expense_date" error={errors.expense_date} required>
                            <DateInput
                                id="expense_date"
                                value={data.expense_date}
                                error={Boolean(errors.expense_date)}
                                onChange={(event) => setData('expense_date', event.target.value)}
                            />
                        </FormField>

                        {needsPeriod ? (
                            <div className="grid grid--2">
                                <FormField
                                    label="Awal periode minggu"
                                    htmlFor="period_start"
                                    error={errors.period_start}
                                    hint="Gaji dicatat per minggu."
                                    required
                                >
                                    <DateInput
                                        id="period_start"
                                        value={data.period_start}
                                        error={Boolean(errors.period_start)}
                                        onChange={(event) => setData('period_start', event.target.value)}
                                    />
                                </FormField>

                                <FormField
                                    label="Akhir periode minggu"
                                    htmlFor="period_end"
                                    error={errors.period_end}
                                    required
                                >
                                    <DateInput
                                        id="period_end"
                                        value={data.period_end}
                                        error={Boolean(errors.period_end)}
                                        onChange={(event) => setData('period_end', event.target.value)}
                                    />
                                </FormField>
                            </div>
                        ) : null}

                        <FormField
                            label="Nominal"
                            htmlFor="amount"
                            error={errors.amount}
                            hint="Total, bukan per karyawan."
                            required
                        >
                            <CurrencyInput
                                id="amount"
                                value={data.amount}
                                placeholder="0"
                                error={Boolean(errors.amount)}
                                onChange={(value) => setData('amount', value)}
                            />
                        </FormField>

                        <FormField label="Catatan" htmlFor="note" error={errors.note}>
                            <TextInput
                                id="note"
                                value={data.note}
                                placeholder="Contoh: token listrik outlet pusat"
                                onChange={(event) => setData('note', event.target.value)}
                            />
                        </FormField>

                        <div className="actions">
                            <Button type="submit" processing={processing} processingLabel="Menyimpan">
                                {editing ? 'Simpan perubahan' : 'Simpan'}
                            </Button>
                            <Link href="/expenses" className="btn btn--outline">
                                Batal
                            </Link>
                        </div>
                    </div>
                </form>
            </Card>
        </AppShell>
    );
}
