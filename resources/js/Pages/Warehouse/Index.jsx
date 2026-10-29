import { Head, Link, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import Card from '../../Components/Card';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

/**
 * Menu Gudang sengaja hanya berisi dua pintu masuk supaya tidak membingungkan.
 * Aksi lanjutan (kategori, supplier, min stok) tetap ada di dalam halaman barang.
 */
export default function Index({ counts }) {
    const { auth } = usePage().props;

    const menus = [
        {
            title: 'Kelola Outlet',
            description: 'Tambah outlet baru atau hapus outlet yang tidak dipakai.',
            meta: `${number(counts.outlets)} outlet terdaftar`,
            primary: { href: '/outlets?new=1', label: 'Tambah outlet' },
            secondary: { href: '/outlets', label: 'Lihat & hapus outlet' },
        },
        {
            title: 'Kelola Supplier',
            description: 'Tambah barang, ubah harga (Rupiah), dan ganti satuan (bawaan pcs).',
            meta: `${number(counts.products)} barang terdaftar`,
            primary: { href: '/products/create', label: 'Tambah barang' },
            secondary: { href: '/products', label: 'Lihat, ubah harga & hapus barang' },
        },
    ];

    return (
        <AppShell
            title="Kelola gudang"
            subtitle="Dua menu utama"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Kelola gudang" />

            <div className="grid grid--2">
                {menus.map((menu) => (
                    <Card key={menu.title} title={menu.title} subtitle={menu.description}>
                        <p className="muted">{menu.meta}</p>

                        <div className="actions">
                            <Link href={menu.primary.href} className="btn">
                                {menu.primary.label}
                            </Link>
                            <Link href={menu.secondary.href} className="btn btn--outline">
                                {menu.secondary.label}
                            </Link>
                        </div>
                    </Card>
                ))}
            </div>
        </AppShell>
    );
}
