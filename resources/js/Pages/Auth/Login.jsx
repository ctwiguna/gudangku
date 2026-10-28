import { Head, Link, useForm } from '@inertiajs/react';
import Button from '../../Components/Button';
import FormField from '../../Components/FormField';
import TextInput from '../../Components/TextInput';
import AuthLayout from '../../Layouts/AuthLayout';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event) {
        event.preventDefault();

        post('/login', {
            onFinish: () => reset('password'),
        });
    }

    return (
        <AuthLayout title="Masuk" description="Gunakan akun yang sudah diaktifkan owner.">
            <Head title="Masuk" />

            <form onSubmit={submit} noValidate>
                <div className="stack">
                    <FormField label="Email" htmlFor="email" error={errors.email} required>
                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            autoComplete="username"
                            autoFocus
                            placeholder="nama@outlet.test"
                            error={Boolean(errors.email)}
                            onChange={(event) => setData('email', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Password" htmlFor="password" error={errors.password} required>
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            autoComplete="current-password"
                            error={Boolean(errors.password)}
                            onChange={(event) => setData('password', event.target.value)}
                        />
                    </FormField>

                    <label className="checkbox">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                        />
                        <span>Ingat saya di perangkat ini</span>
                    </label>

                    <Button type="submit" processing={processing} processingLabel="Memeriksa">
                        Masuk
                    </Button>
                </div>
            </form>

            <p className="muted auth-shell__switch">
                Belum punya akun? <Link href="/register">Daftar sebagai manajer outlet</Link>
            </p>
        </AuthLayout>
    );
}
