import { Head, Link } from '@inertiajs/react';

export default function Forbidden() {
    return (
        <>
            <Head title="Akses ditolak" />

            <main className="container">
                <div className="card" style={{ marginTop: 'var(--space-8)', textAlign: 'center' }}>
                    <p className="badge badge--danger">403</p>
                    <h1 style={{ fontSize: 'var(--text-title)', marginTop: 'var(--space-3)' }}>Akses ditolak</h1>
                    <p className="muted">
                        Halaman ini bukan untuk role Anda, atau data yang dibuka milik outlet lain.
                        Menu yang tersedia hanya yang sesuai hak akses akun Anda.
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
