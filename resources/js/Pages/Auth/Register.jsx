import { Head, Link, useForm } from '@inertiajs/react';
import Button from '../../Components/Button';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';
import AuthLayout from '../../Layouts/AuthLayout';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();

        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    }

    return (
        <AuthLayout
            title="Daftar akun"
            description="Pendaftaran membuat akun manajer outlet yang menunggu aktivasi owner."
        >
            <Head title="Daftar" />

            <form onSubmit={submit} noValidate>
                <div className="stack">
                    <FormField label="Nama" htmlFor="name" error={errors.name} required>
                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            autoComplete="name"
                            autoFocus
                            error={Boolean(errors.name)}
                            onChange={(event) => setData('name', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Email" htmlFor="email" error={errors.email} required>
                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            autoComplete="username"
                            placeholder="nama@outlet.test"
                            error={Boolean(errors.email)}
                            onChange={(event) => setData('email', event.target.value)}
                        />
                    </FormField>

                    <FormField
                        label="Password"
                        htmlFor="password"
                        error={errors.password}
                        hint="Minimal 8 karakter."
                        required
                    >
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            autoComplete="new-password"
                            error={Boolean(errors.password)}
                            onChange={(event) => setData('password', event.target.value)}
                        />
                    </FormField>

                    <FormField
                        label="Ulangi password"
                        htmlFor="password_confirmation"
                        error={errors.password_confirmation}
                        required
                    >
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                        />
                    </FormField>

                    <Button type="submit" processing={processing} processingLabel="Mendaftarkan">
                        Daftar
                    </Button>
                </div>
            </form>

            <p className="muted auth-shell__switch">
                Sudah punya akun? <Link href="/login">Masuk</Link>
            </p>
        </AuthLayout>
    );
}
