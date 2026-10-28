import { Head, useForm, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Button from '../../Components/Button';
import Card from '../../Components/Card';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';
import AppShell from '../../Layouts/AppShell';

export default function Edit({ profile }) {
    const { auth } = usePage().props;

    const form = useForm({
        name: profile.name,
        email: profile.email,
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const { data, setData, errors, processing } = form;
    const errorList = Object.values(errors);

    function submit(event) {
        event.preventDefault();
        form.put('/profile', {
            preserveScroll: true,
            onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
        });
    }

    return (
        <AppShell
            title="Akun"
            subtitle="Profil dan password Anda sendiri"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Akun" />

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

            <Card title="Informasi akun" subtitle="Role dan outlet hanya bisa diubah owner">
                <ul className="list">
                    <li className="list__row">
                        <span className="muted">Role</span>
                        <span>{profile.role_label}</span>
                    </li>
                    <li className="list__row">
                        <span className="muted">Outlet</span>
                        <span>
                            {profile.outlet
                                ? `${profile.outlet} (${profile.outlet_code})`
                                : 'Tidak terikat outlet'}
                        </span>
                    </li>
                </ul>
            </Card>

            <Card title="Ubah profil">
                <form onSubmit={submit} noValidate>
                    <div className="stack">
                        <FormField label="Nama" htmlFor="name" error={errors.name} required>
                            <TextInput
                                id="name"
                                value={data.name}
                                error={Boolean(errors.name)}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </FormField>

                        <FormField label="Email" htmlFor="email" error={errors.email} required>
                            <TextInput
                                id="email"
                                type="email"
                                value={data.email}
                                error={Boolean(errors.email)}
                                onChange={(event) => setData('email', event.target.value)}
                            />
                        </FormField>

                        <FormField
                            label="Password lama"
                            htmlFor="current_password"
                            error={errors.current_password}
                            hint="Wajib diisi hanya kalau ingin mengganti password."
                        >
                            <TextInput
                                id="current_password"
                                type="password"
                                value={data.current_password}
                                autoComplete="current-password"
                                error={Boolean(errors.current_password)}
                                onChange={(event) => setData('current_password', event.target.value)}
                            />
                        </FormField>

                        <div className="grid grid--2">
                            <FormField
                                label="Password baru"
                                htmlFor="password"
                                error={errors.password}
                                hint="Minimal 8 karakter."
                            >
                                <TextInput
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    autoComplete="new-password"
                                    error={Boolean(errors.password)}
                                    onChange={(event) => setData('password', event.target.value)}
                                />
                            </FormField>

                            <FormField
                                label="Ulangi password baru"
                                htmlFor="password_confirmation"
                                error={errors.password_confirmation}
                            >
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    autoComplete="new-password"
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                />
                            </FormField>
                        </div>

                        <div className="actions">
                            <Button type="submit" processing={processing} processingLabel="Menyimpan">
                                Simpan profil
                            </Button>
                        </div>
                    </div>
                </form>
            </Card>
        </AppShell>
    );
}
