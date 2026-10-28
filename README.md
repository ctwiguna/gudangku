# gudangku

Aplikasi pencatatan dan stock opname inventaris outlet.

Mengelola stok barang di beberapa outlet, mencatat penerimaan barang dari supplier, mencatat pembelian beserta harga per pcs, serta mencatat pengeluaran operasional per outlet seperti listrik, air, IPAL, gaji mingguan, sewa, dan biaya lain.

- Live: **https://43.173.41.195.sslip.io**
- Repository: https://github.com/ctwiguna/gudangku
- Copyright 2026 Chandra TW. Dibuat untuk UAS Web Application Development, Universitas Cakrawala.

## Peran pengguna

| Peran | Hak akses |
| :--- | :--- |
| Owner | Melihat semua outlet. Mengatur outlet, user, kategori, supplier, dan produk. Menginput harga barang per pcs. Melihat harga, hutang, pengeluaran, dan laporan. Melengkapi harga pembelian dan menandai pembayaran. |
| Manajer outlet | Terikat pada satu outlet. Menerima barang datang dengan input jumlah saja, tanpa harga. Wajib melakukan stock opname sekali seminggu. Tidak bisa melihat harga, total pembelian, atau laporan pengeluaran. |

Register publik hanya membuat akun **manajer nonaktif**. Akun tersebut tidak bisa login sebelum owner mengaktifkan dan menugaskan outlet. Form register tidak memiliki field role.

## Fitur

1. Autentikasi manual: register, login, logout, aktivasi akun oleh owner, dua role, rate limit 5 percobaan per menit.
2. Dashboard owner: pengeluaran bulan berjalan, hutang supplier, antrean pembelian belum berharga, nilai stok, produk di bawah minimum, status opname per outlet, transaksi terakhir.
3. Dashboard manajer: stok outlet sendiri, produk di bawah minimum, pengingat opname mingguan, penerimaan terakhir. Tanpa angka uang.
4. Penerimaan barang oleh manajer tanpa harga, langsung menambah stok pada tanggal terima.
5. Owner melengkapi harga per item; setelah semua item berharga, pembelian masuk hitungan hutang.
6. Tandai pembelian sudah dibayar dan pembatalan penerimaan yang belum berharga.
7. Stock opname mingguan: satu opname per outlet per minggu, stok sistem dihitung ulang saat submit, selisih menulis penyesuaian.
8. Riwayat stok (ledger) sebagai bukti audit saldo.
9. CRUD pengeluaran operasional dengan enam kategori tetap, periode wajib untuk gaji.
10. CRUD produk, kategori, supplier, outlet, dan user.
11. Laporan owner: pengeluaran per kategori dan outlet, hutang, nilai stok, pembelian per periode.
12. Keamanan: CSRF, escaping React, Eloquent binding, rate limit, RBAC middleware + policy, penyaringan harga di controller, halaman 403/404 sendiri.

## Stack

| Lapisan | Teknologi | Versi terpasang |
| :--- | :--- | :--- |
| Backend | Laravel | 13.39.0 |
| Runtime | PHP | 8.5.12 |
| Frontend | React + Inertia.js + Vite | React 19, Inertia 3.5, Vite 8 |
| Database | MySQL | 8.0.46 (server), 26.10.0 (lokal) |
| Styling | CSS variables, komponen sendiri | tanpa UI framework |
| Server | nginx + PHP-FPM | nginx 1.24.0 |
| Node saat build | Node | 22.23.4 (server), 24.15.0 (lokal) |

## Akun contoh (dari seeder)

| Peran | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| Owner | `owner@gudangku.test` | `password` | Akses penuh, tidak terikat outlet |
| Manajer | `manajer.pusat@gudangku.test` | `password` | Outlet Cakrawala Pusat (CKR-01) |
| Manajer | `manajer.cabang@gudangku.test` | `password` | Outlet Cakrawala Cabang (CKR-02) |
| Manajer nonaktif | `manajer.baru@gudangku.test` | `password` | Belum punya outlet, tidak bisa login |

Akun ini hanya untuk keperluan demonstrasi UAS.

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

Build produksi:

```bash
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Pengujian

```bash
php artisan test        # 133 test, 609 assertion
composer audit          # tanpa advisory
npm audit               # 0 vulnerability
```

Skenario uji manual dan hasilnya ada di [docs/UJI.md](docs/UJI.md).

### Ekspor database

User aplikasi sengaja tidak punya hak `RELOAD`/`FLUSH_TABLES`, jadi `mysqldump` perlu dijalankan tanpa lock tabel:

```bash
mysqldump --no-tablespaces --skip-lock-tables --add-drop-table \
  -h 127.0.0.1 -u gudangku -p gudangku > database/gudangku.sql
```

Hasil ekspor contoh ada di `database/gudangku.sql` (19 tabel, data seed 4 akun demo).

## Deploy

Langkah deploy lengkap, termasuk masalah nyata yang muncul saat deploy beserta solusinya, ada di [docs/DEPLOY_VPS.md](docs/DEPLOY_VPS.md).

Ringkas: VPS Ubuntu 24.04, PHP 8.5.12 FPM (PPA `ondrej/php`), nginx, MySQL 8, Node 22, Let's Encrypt, `SESSION_DRIVER=database`, `APP_DEBUG=false`.

## Struktur direktori

```text
app/Enums              UserRole, MovementType, ExpenseCategory
app/Http/Controllers   route handler, penyaringan props per role
app/Http/Requests      validasi server side
app/Http/Middleware    middleware role (EnsureOwner, EnsureManager) dan Inertia
app/Models             Eloquent, relasi, scope outlet
app/Policies           otorisasi per model
app/Services           StockService, StockOpnameService, DashboardService
resources/js/Pages     halaman React
resources/js/Components komponen design system
resources/css/app.css  token warna, tipografi, spasi
routes/web.php         definisi route dan middleware
docs/                  panduan deploy dan catatan uji
database/gudangku.sql  ekspor database contoh
```

## Catatan jujur

1. Server produksi memakai **MySQL 8.0.46**, bukan 26.10.0 seperti tertulis di PRD. Tidak ada fitur aplikasi yang bergantung pada versi tersebut.
2. Domain memakai `sslip.io` karena keterbatasan waktu; mengganti ke domain sendiri hanya perlu mengubah `server_name`, A record, dan menerbitkan ulang sertifikat.
3. Start command produksi memakai `php artisan serve` di belakang nginx. Untuk trafik nyata, ganti ke `php-fpm` + nginx (sudah terpasang) atau FrankenPHP.
4. `CVE-2026-45070` pada `symfony/mime` sudah dipatch dengan menaikkan ke v8.1.10; `composer audit` bersih.

## Lisensi pihak ketiga

Laravel (MIT), React (MIT), Inertia.js (MIT), Vite (MIT), dan library lain memakai lisensi masing-masing. Nama dan merek pihak ketiga tetap milik pemiliknya.
