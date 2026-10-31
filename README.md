# gudangku

**Catat, monitor dan audit stok + Pengeluaran bisnis**

Aplikasi pencatatan dan stock opname inventaris outlet. Tagline di atas dipakai di layar login, header aplikasi, dan catatan kaki; nilainya diatur lewat `APP_TAGLINE` sehingga cukup diubah di satu tempat.

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
13. **Ekspor Excel** pengeluaran per outlet per bulan (`.xlsx`, empat sheet: **Ringkasan** total per outlet per bulan, **Per Kategori** pengeluaran per bulan per kategori (listrik, air, ipal, gaji, sewa, lain) dengan subtotal bulanan dan total tahunan per outlet, **Pembelian Produk** pembelian per bulan per kategori produk (mis. Gas LPG) dengan subtotal, dan **Rincian** daftar datar untuk pivot).
14. **Menu Gudang** (tab tengah navigasi owner) berisi **empat kartu yang seluruhnya bisa diklik** — satu kartu, satu klik, tanpa tombol di dalam kartu:
    - **Kelola Outlet** — tambah, ubah, dan hapus outlet (kolom ID dan Nama Outlet).
    - **Database Supplier** — informasi lengkap pemasok: ID, nama, kontak/telepon, alamat, **lead time (hari pengiriman)**, dan **term of payment (TOP) dalam hari** (contoh: 7, 14, atau 30 hari).
    - **Harga Barang** — kode barang, nama barang, **satuan (pcs/dus/kg, bawaan pcs)**, ID supplier, **harga satuan dalam Rupiah**, dan **tanggal update harga** (diisi otomatis oleh server).
    - **Kelola Karyawan** — daftar karyawan **dikelompokkan per outlet**, berisi nama karyawan, gaji, dan satuan gaji (harian, mingguan, bulanan). Outlet wajib dipilih dan daftar bisa disaring per outlet.
15. **Detail hutang supplier** — kartu "Hutang supplier" di dashboard menampilkan nominal **dan** jumlah pembelian yang belum dibayar, bertuliskan *Klik lebih detail*, dan seluruh kartunya bisa diklik menuju halaman **Hutang Supplier**: daftar barang per supplier (nama produk, jumlah, harga satuan, subtotal), total tiap supplier, filter outlet, tombol **Tandai dibayar**, dan tautan ke detail penerimaan.

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

## Akun pengujian

Semua akun memakai password `hidupberkah`. Panduan langkah demi langkah untuk penguji ada di [docs/PANDUAN_PENGUJI.md](docs/PANDUAN_PENGUJI.md).

| Peran | Email | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| Owner | `owner@gudangku.test` | `hidupberkah` | Akses penuh, tidak terikat outlet |
| Manajer | `manajer1@gudangku.test` | `hidupberkah` | Outlet Kedungmundu (KDM-01) |
| Manajer | `manajer2@gudangku.test` | `hidupberkah` | Outlet Alton (ALT-01) |
| Manajer | `manajer3@gudangku.test` | `hidupberkah` | Outlet Tembalang (TMB-01) |

`migrate --seed` juga membuat satu akun nonaktif `kandidat@gudangku.test` untuk memperagakan alur registrasi dan aktivasi oleh owner.

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

`migrate --seed` hanya mengisi **data dasar**: outlet, akun pengguna, dan karyawan. Data contoh produk, kategori, supplier, pembelian, pengeluaran, dan stock opname sengaja dipisah supaya aplikasi bisa dimulai dari data kosong dan diisi sendiri lewat UI.

Kalau ingin data contoh lengkap (data inilah yang dipakai test otomatis):

```bash
php artisan db:seed --class=DemoDataSeeder
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
php artisan test        # 192 test, 818 assertion
composer audit          # tanpa advisory
npm audit               # 0 vulnerability
```

Skenario uji manual dan hasilnya ada di [docs/UJI.md](docs/UJI.md). Panduan singkat untuk penguji ada di [docs/PANDUAN_PENGUJI.md](docs/PANDUAN_PENGUJI.md).

### Ekspor database

User aplikasi sengaja tidak punya hak `RELOAD`/`FLUSH_TABLES`, jadi `mysqldump` perlu dijalankan tanpa lock tabel:

```bash
mysqldump --no-tablespaces --skip-lock-tables --add-drop-table \
  -h 127.0.0.1 -u gudangku -p gudangku > database/gudangku.sql
```

Hasil ekspor contoh ada di `database/gudangku.sql` (19 tabel, data seed 4 akun demo).

## Keamanan sesi

Aplikasi **tidak menyimpan kunci apa pun di localStorage** — tidak ada access token, refresh token, atau JWT. Yang ada di browser hanya ID sesi pada cookie yang tidak bisa dibaca JavaScript.

| Kontrol | Nilai |
| :--- | :--- |
| Cookie sesi | `HttpOnly`, `Secure` (produksi), `SameSite=Lax` |
| Cookie CSRF | `XSRF-TOKEN`, sengaja bisa dibaca JavaScript (pola double-submit), bukan kredensial |
| Regenerasi sesi | `regenerate()` saat login, `invalidate()` + `regenerateToken()` saat logout |
| Idle timeout | 60 menit tanpa aktivitas (`SESSION_LIFETIME`) |
| Absolute timeout | 720 menit sejak login (`SESSION_ABSOLUTE_LIFETIME`), dipaksa middleware `EnsureSessionIsFresh` |
| Tutup browser | sesi berakhir (`SESSION_EXPIRE_ON_CLOSE=true`) — penting untuk perangkat bersama di outlet |
| Payload sesi | dienkripsi di database (`SESSION_ENCRYPT=true`) |
| Ganti password | seluruh sesi lain milik user itu dicabut + `remember_token` dikosongkan (`SessionService::revokeForUser()`) |
| Nonaktifkan user | sesi + token remember user tersebut langsung dicabut; middleware `EnsureUserIsActive` menendang akun nonaktif di request berikutnya |
| "Ingat saya" | dihapus total (31 Okt 2026) — audit menemukan cookie recaller melewati pemeriksaan akun nonaktif dan absolute timeout |
| Rate limit login | 5 percobaan per menit per email + IP |
| Rate limit register | 5 percobaan per menit per IP |
| Session fixation | dicegah dengan regenerasi ID sesi saat login |

Analisis lengkap pertimbangan localStorage vs cookie HttpOnly, idle vs absolute timeout, dan kapan refresh token diperlukan ada di [docs/ANALISIS_SESI.md](docs/ANALISIS_SESI.md).

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
docs/                  panduan penguji, deploy, dan catatan uji
database/gudangku.sql  ekspor database contoh
```

## Catatan jujur

1. **Ekspor Excel dan Kelola Karyawan berada di luar scope PRD.** PRD §5 menaruh "ekspor Excel atau PDF laporan" dan "gaji per karyawan" sebagai fitur di luar scope, dan keduanya ditambahkan atas permintaan setelah aplikasi selesai. Dicatat sebagai penyimpangan yang disengaja. Library ekspor: `phpoffice/phpspreadsheet` (MIT). Data karyawan hanya acuan (nama, gaji, satuan gaji); pengeluaran gaji per outlet tetap dicatat di menu Pengeluaran kategori gaji.
2. Server produksi memakai **MySQL 8.0.46**, bukan 26.10.0 seperti tertulis di PRD. Tidak ada fitur aplikasi yang bergantung pada versi tersebut.
3. Domain memakai `sslip.io` karena keterbatasan waktu; mengganti ke domain sendiri hanya perlu mengubah `server_name`, A record, dan menerbitkan ulang sertifikat.
4. Start command produksi memakai `php artisan serve` di belakang nginx. Untuk trafik nyata, ganti ke `php-fpm` + nginx (sudah terpasang) atau FrankenPHP.
5. `CVE-2026-45070` pada `symfony/mime` sudah dipatch dengan menaikkan ke v8.1.10; `composer audit` bersih.

## Lisensi pihak ketiga

Laravel (MIT), React (MIT), Inertia.js (MIT), Vite (MIT), dan library lain memakai lisensi masing-masing. Nama dan merek pihak ketiga tetap milik pemiliknya.
