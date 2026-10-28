import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="Beranda" />

            <main className="container">
                <header style={{ marginBottom: 'var(--space-6)' }}>
                    <p className="badge">Versi 0.1 · fondasi</p>
                    <h1 style={{ fontSize: 'var(--text-display)', marginTop: 'var(--space-3)' }}>
                        gudangku
                    </h1>
                    <p className="muted" style={{ marginTop: 'var(--space-2)' }}>
                        Pencatatan dan stock opname inventaris outlet.
                    </p>
                </header>

                <section className="card">
                    <h2 style={{ fontSize: 'var(--text-title-sm)' }}>Fondasi terpasang</h2>
                    <p className="muted" style={{ marginTop: 'var(--space-2)' }}>
                        Laravel 13.39.0 · Inertia.js · React 19 · Vite · MySQL 26.10.0
                    </p>
                </section>
            </main>
        </>
    );
}
