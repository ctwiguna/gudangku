import { Head, router, usePage } from '@inertiajs/react';
import Button from '../../Components/Button';
import Toast from '../../Components/Toast';

export default function Manager() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard stok" />

            <div className="container">
                <Toast />

                <header className="page-header">
                    <div>
                        <p className="badge">{auth.user.role_label}</p>
                        <h1 className="page-header__title">Dashboard stok</h1>
                        <p className="muted">
                            {auth.user.outlet
                                ? `${auth.user.outlet.name} (${auth.user.outlet.code})`
                                : 'Outlet belum ditugaskan'}
                        </p>
                    </div>
                </header>

                <section className="card">
                    <h2 className="card__title">Kerangka dashboard</h2>
                    <p className="muted">
                        Stok outlet sendiri, produk di bawah minimum, pengingat opname, dan
                        penerimaan terakhir ditambahkan pada tahap dashboard.
                    </p>
                    <p className="muted">
                        Halaman ini tidak menerima angka uang sama sekali dari server.
                    </p>
                </section>

                <div className="actions">
                    <Button variant="outline" onClick={() => router.post('/logout')}>
                        Keluar
                    </Button>
                </div>
            </div>
        </>
    );
}
