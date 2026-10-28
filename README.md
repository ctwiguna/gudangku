# gudangku

Aplikasi pencatatan dan stock opname inventaris outlet.

Mengelola stok barang di beberapa outlet, mencatat penerimaan barang dari supplier, mencatat pembelian beserta harga per pcs, serta mencatat pengeluaran operasional per outlet seperti listrik, air, IPAL, gaji mingguan, sewa, dan biaya lain.

- Live: **https://43.173.41.195.sslip.io**
- Repository: https://github.com/ctwiguna/gudangku
- Copyright 2026 Chandra TW. Dibuat untuk UAS Web Application Development, Universitas Cakrawala.

## Stack

| Lapisan | Teknologi | Versi terpasang |
| :--- | :--- | :--- |
| Backend | Laravel | 13.39.0 |
| Runtime | PHP | 8.5.12 |
| Frontend | React + Inertia.js + Vite | React 19, Inertia 3.5, Vite 8 |
| Database | MySQL | 8.0.46 (server), 26.10.0 (lokal) |
| Styling | CSS variables, komponen sendiri | tanpa UI framework |
| Server | nginx + PHP-FPM | nginx 1.24.0 |

## Peran pengguna

| Peran | Hak akses |
| :--- | :--- |
| Owner | Melihat semua outlet. Mengatur outlet, user, kategori, supplier, dan produk. Menginput harga barang per pcs. Melihat harga, hutang, pengeluaran, dan laporan. Melengkapi harga pembelian dan menandai pembayaran. |
| Manajer outlet | Terikat pada satu outlet. Menerima barang datang dengan input jumlah saja, tanpa harga. Wajib melakukan stock opname sekali seminggu. Tidak bisa melihat harga, total pembelian, atau laporan pengeluaran. |

Register publik hanya membuat akun **manajer nonaktif**. Akun tersebut tidak bisa login sebelum owner mengaktifkan dan menugaskan outlet. Form register tidak memiliki field role.

## Instalasi lokal

Prasyarat: PHP 8.5, Composer 2, Node 20 atau lebih baru, MySQL 8.

```bash
git clone https://github.com/ctwiguna/gudangku.git
cd gudangku

composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Menyiapkan database

Buat database dan user khusus aplikasi. Aplikasi **tidak** memakai user root:

```sql
CREATE DATABASE gudangku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gudangku'@'127.0.0.1' IDENTIFIED BY 'gudangku_local';
GRANT ALL PRIVILEGES ON gudangku.* TO 'gudangku'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`, lalu:

```bash
php artisan migrate --seed
```

### Menjalankan

```bash
npm run dev          # terminal 1: Vite
php artisan serve    # terminal 2: http://localhost:8000
```

Untuk build produksi:

```bash
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Akun contoh (dari seeder)

| Peran | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| Owner | `owner@gudangku.test` | `password` | Akses penuh |
| Manajer | `manajer.pusat@gudangku.test` | `password` | Outlet Cakrawala Pusat |
| Manajer | `manajer.cabang@gudangku.test` | `password` | Outlet Cakrawala Cabang |
| Manajer nonaktif | `manajer.baru@gudangku.test` | `password` | Belum punya outlet, tidak bisa login |

Akun ini hanya untuk keperluan demonstrasi UAS.

## Pengujian

```bash
php artisan test
composer audit
npm audit
```

## Deploy

Langkah deploy lengkap, termasuk masalah yang muncul saat deploy dan solusinya, ada di [docs/DEPLOY_VPS.md](docs/DEPLOY_VPS.md).

## Struktur ringkas

```text
app/Http/Controllers   route handler, penyaringan props per role
app/Http/Requests      validasi server side
app/Http/Middleware    middleware role dan Inertia
app/Models             Eloquent, relasi, scope outlet
app/Policies           otorisasi per model
app/Services           logika stok, pembelian, opname
resources/js/Pages     halaman React
resources/js/Components komponen design system
resources/css/app.css  token warna, tipografi, spasi
routes/web.php         definisi route dan middleware
```

## Lisensi pihak ketiga

Laravel (MIT), React (MIT), Inertia.js (MIT), Vite (MIT). Nama dan merek pihak ketiga tetap milik pemiliknya.
