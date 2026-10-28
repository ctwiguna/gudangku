import { Head, router, usePage } from '@inertiajs/react';
import StatCard from '../../Components/StatCard';
import Card from '../../Components/Card';
import EmptyState from '../../Components/EmptyState';
import Button from '../../Components/Button';
import AppShell from '../../Layouts/AppShell';

export default function Owner() {
    const { auth } = usePage().props;

    return (
        <AppShell
            title="Dashboard owner"
            subtitle={auth.user.name}
            badge={<span className="badge">{auth.user.role_label}</span>}
            actions={
                <Button variant="outline" onClick={() => router.post('/logout')}>
                    Keluar
                </Button>
            }
        >
            <Head title="Dashboard owner" />

            <div className="stat-grid">
                <StatCard label="Pengeluaran bulan ini" value={0} money tone="accent" />
                <StatCard label="Hutang supplier" value={0} money tone="danger" />
                <StatCard label="Antrean belum berharga" value={0} hint="pembelian" />
                <StatCard label="Nilai stok" value={0} money />
            </div>

            <Card title="Status opname per outlet" subtitle="Minggu berjalan">
                <EmptyState
                    title="Data agregat belum dipasang"
                    description="Kartu pengeluaran, hutang supplier, antrean harga, nilai stok, produk di bawah minimum, dan status opname diisi pada tahap dashboard."
                />
            </Card>
        </AppShell>
    );
}
