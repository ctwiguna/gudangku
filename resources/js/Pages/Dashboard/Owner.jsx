import { Head, router, usePage } from '@inertiajs/react';
import Button from '../../Components/Button';
import Toast from '../../Components/Toast';

export default function Owner() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard owner" />

            <div className="container">
                <Toast />

                <header className="page-header">
                    <div>
                        <p className="badge">{auth.user.role_label}</p>
                        <h1 className="page-header__title">Dashboard owner</h1>
                        <p className="muted">{auth.user.name}</p>
                    </div>
                </header>

                <section className="card">
                    <h2 className="card__title">Kerangka dashboard</h2>
                    <p className="muted">
                        Kartu pengeluaran, hutang supplier, antrean belum berharga, nilai stok,
                        produk kritis, dan status opname ditambahkan pada tahap dashboard.
                    </p>
                    <p className="muted">
                        Owner tidak terikat satu outlet, jadi seluruh outlet terlihat di sini.
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
