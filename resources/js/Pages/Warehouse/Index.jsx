import { Head, Link, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Card from '../../Components/Card';
import StatCard from '../../Components/StatCard';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

export default function Index({ counts }) {
    const { auth } = usePage().props;

    const quickActions = [
        { href: '/products/create', label: 'Tambah produk' },
        { href: '/master-data?tab=kategori&new=1', label: 'Tambah kategori' },
        { href: '/master-data?tab=supplier&new=1', label: 'Tambah supplier' },
        { href: '/outlets?new=1', label: 'Tambah outlet' },
    ];

    const sections = [
        {
            href: '/products',
            title: 'Produk',
            description: 'Daftar produk, stok dari ledger, harga per pcs, dan status aktif.',
            meta: `${number(counts.products_active)} aktif dari ${number(counts.products)} produk`,
        },
        {
            href: '/master-data',
            title: 'Kategori & supplier',
            description: 'Kelola kategori produk dan supplier dalam satu halaman bertab.',
            meta: `${number(counts.categories)} kategori · ${number(counts.suppliers)} supplier`,
        },
        {
            href: '/purchases',
            title: 'Pembelian & harga',
            description: 'Antrean menunggu harga, total pembelian, dan penandaan pembayaran.',
            meta: `${number(counts.unpriced)} menunggu harga`,
        },
        {
            href: '/receipts',
            title: 'Penerimaan barang',
            description: 'Riwayat barang datang dari supplier, tanpa harga untuk manajer.',
            meta: 'semua outlet',
        },
        {
            href: '/stock-opnames',
            title: 'Stock opname',
            description: 'Opname mingguan per outlet dan riwayat hasilnya.',
            meta: 'mingguan',
        },
        {
            href: '/stock-movements',
            title: 'Riwayat stok',
            description: 'Ledger pergerakan stok sebagai bukti audit saldo.',
            meta: 'in · out · penyesuaian',
        },
        {
            href: '/outlets',
            title: 'Outlet',
            description: 'Tambah, ubah, aktifkan, atau nonaktifkan outlet.',
            meta: `${number(counts.outlets)} outlet`,
        },
    ];

    return (
        <AppShell
            title="Gudang"
            subtitle="Master data, transaksi barang, dan audit stok"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Gudang" />

            <div className="stat-grid">
                <StatCard label="Produk" value={number(counts.products)} hint="terdaftar" />
                <StatCard label="Kategori" value={number(counts.categories)} hint="kategori produk" />
                <StatCard label="Supplier" value={number(counts.suppliers)} hint="terdaftar" />
                <StatCard
                    label="Menunggu harga"
                    value={number(counts.unpriced)}
                    tone={counts.unpriced > 0 ? 'accent' : 'default'}
                    hint="pembelian"
                />
            </div>

            <Card title="Tambah cepat" subtitle="Aksi yang paling sering dipakai owner">
                <div className="quick-actions">
                    {quickActions.map((action) => (
                        <Link key={action.href} href={action.href} className="btn">
                            {action.label}
                        </Link>
                    ))}
                </div>
            </Card>

            <Card title="Kelola gudang" subtitle="Pilih bagian yang ingin dibuka">
                <ul className="list">
                    {sections.map((section) => (
                        <li key={section.href} className="list__row">
                            <div className="list__main">
                                <p className="list__title">
                                    <Link href={section.href}>{section.title}</Link>
                                </p>
                                <p className="muted">{section.description}</p>
                            </div>
                            <span className="muted">{section.meta}</span>
                        </li>
                    ))}
                </ul>
            </Card>
        </AppShell>
    );
}
