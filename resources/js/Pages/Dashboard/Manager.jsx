import { Head, router, usePage } from '@inertiajs/react';
import StatCard from '../../Components/StatCard';
import Card from '../../Components/Card';
import EmptyState from '../../Components/EmptyState';
import Button from '../../Components/Button';
import AppShell from '../../Layouts/AppShell';

export default function Manager() {
    const { auth } = usePage().props;

    return (
        <AppShell
            title="Dashboard stok"
            subtitle={auth.user.outlet ? `${auth.user.outlet.name} (${auth.user.outlet.code})` : 'Outlet belum ditugaskan'}
            badge={<span className="badge">{auth.user.role_label}</span>}
            reminder
            actions={
                <Button variant="outline" onClick={() => router.post('/logout')}>
                    Keluar
                </Button>
            }
        >
            <Head title="Dashboard stok" />

            <div className="stat-grid">
                <StatCard label="Jumlah produk aktif" value={0} hint="produk" />
                <StatCard label="Produk di bawah minimum" value={0} hint="perlu restock" tone="accent" />
                <StatCard label="Penerimaan minggu ini" value={0} hint="penerimaan" />
            </div>

            <Card title="Stok outlet sendiri" subtitle="Tanpa angka uang">
                <EmptyState
                    title="Data agregat belum dipasang"
                    description="Stok outlet, produk di bawah minimum, pengingat opname, dan penerimaan terakhir diisi pada tahap dashboard. Halaman ini tidak menerima field harga dari server."
                />
            </Card>
        </AppShell>
    );
}
