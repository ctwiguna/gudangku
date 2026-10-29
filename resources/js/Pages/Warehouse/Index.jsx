import { Head, Link, usePage } from '@inertiajs/react';
import Badge from '../../Components/Badge';
import AppShell from '../../Layouts/AppShell';
import { number } from '../../utils/format';

/**
 * Menu Gudang: satu kartu = satu klik. Tidak ada tombol di dalam kartu,
 * aksi tambah/ubah/hapus ada di dalam halaman masing-masing.
 */
export default function Index({ counts }) {
    const { auth } = usePage().props;

    const menus = [
        {
            href: '/outlets',
            title: 'Kelola Outlet',
            description: 'Tambah, ubah, dan hapus outlet.',
            meta: `${number(counts.outlets)} outlet`,
        },
        {
            href: '/suppliers',
            title: 'Database Supplier',
            description: 'Kontak, alamat, lead time, dan term of payment pemasok.',
            meta: `${number(counts.suppliers)} supplier`,
        },
        {
            href: '/products',
            title: 'Harga Barang',
            description: 'Kode, nama, satuan, supplier, harga satuan, dan tanggal update harga.',
            meta: `${number(counts.products)} barang`,
        },
        {
            href: '/employees',
            title: 'Kelola Karyawan',
            description: 'Nama karyawan, gaji, dan satuan gaji (harian, mingguan, bulanan).',
            meta: `${number(counts.employees)} karyawan`,
        },
    ];

    return (
        <AppShell
            title="Kelola gudang"
            subtitle="Pilih satu menu"
            badge={<Badge>{auth.user.role_label}</Badge>}
        >
            <Head title="Kelola gudang" />

            <div className="grid grid--2">
                {menus.map((menu) => (
                    <Link key={menu.href} href={menu.href} className="card card--link">
                        <h2 className="card__title">{menu.title}</h2>
                        <p className="muted">{menu.description}</p>
                        <span className="card__meta">{menu.meta}</span>
                    </Link>
                ))}
            </div>
        </AppShell>
    );
}
