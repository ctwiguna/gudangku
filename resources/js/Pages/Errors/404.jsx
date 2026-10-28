import { Head, Link } from '@inertiajs/react';

export default function NotFound() {
    return (
        <>
            <Head title="Halaman tidak ditemukan" />

            <main className="container">
                <div className="card" style={{ marginTop: 'var(--space-8)', textAlign: 'center' }}>
                    <p className="badge badge--muted">404</p>
                    <h1 style={{ fontSize: 'var(--text-title)', marginTop: 'var(--space-3)' }}>
                        Halaman tidak ditemukan
                    </h1>
                    <p className="muted">
                        Alamat yang Anda buka tidak ada. Mungkin data sudah dihapus atau tautannya salah.
                    </p>

                    <div className="actions" style={{ justifyContent: 'center' }}>
                        <Link href="/dashboard" className="btn">
                            Kembali ke dashboard
                        </Link>
                    </div>
                </div>
            </main>
        </>
    );
}
