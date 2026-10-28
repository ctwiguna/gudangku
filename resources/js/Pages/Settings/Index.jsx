import { Head, Link, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Card from '../../Components/Card';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

export default function Index({ counts }) {
    const { auth } = usePage().props;

    const links = [
        {
            href: '/outlets',
            title: 'Outlet',
            description: 'Tambah, ubah, aktifkan, atau nonaktifkan outlet.',
            meta: `${number(counts.outlets_active)} dari ${number(counts.outlets)} outlet aktif`,
        },
        {
            href: '/users',
            title: 'User',
            description: 'Atur role, penugasan outlet, dan aktivasi akun.',
            meta: `${number(counts.users)} user, ${number(counts.users_inactive)} menunggu aktivasi`,
        },
        {
            href: '/master-data',
            title: 'Kategori & supplier',
            description: 'Master data untuk produk dan penerimaan barang.',
            meta: `${number(counts.categories)} kategori, ${number(counts.suppliers)} supplier`,
        },
        {
            href: '/products',
            title: 'Produk',
            description: 'Harga per pcs, stok minimum, dan status aktif produk.',
            meta: `${number(counts.products)} produk`,
        },
        {
            href: '/reports',
            title: 'Laporan',
            description: 'Pengeluaran per kategori dan outlet, hutang supplier, dan nilai stok.',
            meta: 'khusus owner',
        },
        {
            href: '/stock-movements',
            title: 'Riwayat stok',
            description: 'Ledger pergerakan stok untuk audit saldo per outlet.',
            meta: 'owner dan manajer',
        },
    ];

    return (
        <AppShell
            title="Pengaturan"
            subtitle="Outlet, user, dan master data"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Pengaturan" />

            <div className="stat-grid">
                <StatCard label="Outlet" value={number(counts.outlets)} hint="terdaftar" />
                <StatCard label="User" value={number(counts.users)} hint="semua role" />
                <StatCard
                    label="Menunggu aktivasi"
                    value={number(counts.users_inactive)}
                    tone={counts.users_inactive > 0 ? 'accent' : 'default'}
                    hint="akun nonaktif"
                />
                <StatCard label="Produk" value={number(counts.products)} hint="terdaftar" />
            </div>

            <Card title="Kelola" subtitle="Halaman pengaturan yang tersedia">
                <ul className="list">
                    {links.map((link) => (
                        <li key={link.href} className="list__row">
                            <div className="list__main">
                                <p className="list__title">
                                    <Link href={link.href}>{link.title}</Link>
                                </p>
                                <p className="muted">{link.description}</p>
                            </div>
                            <span className="muted">{link.meta}</span>
                        </li>
                    ))}
                </ul>
            </Card>
        </AppShell>
    );
}
