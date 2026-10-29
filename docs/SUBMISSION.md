# Dokumen Submission Final — gudangku

Kerangka untuk disalin ke template PDF submission UAS. Ganti bagian bertanda `[ ]` setelah video direkam.

## 1. Identitas

| Item | Isi |
| :--- | :--- |
| Nama | Chandra TW |
| NIM | 25110300017 |
| Kelas / Mata kuliah | Web Application Development (WAD04) |
| Institusi | Universitas Cakrawala |
| Dosen | [isi nama dosen] |
| Tanggal pengumpulan | [isi tanggal] |

## 2. Informasi project

| Item | Isi |
| :--- | :--- |
| Judul | gudangku — aplikasi pencatatan dan stock opname inventaris outlet |
| Jenis | Aplikasi web fullstack (Laravel + Inertia.js + React) |
| Repository | https://github.com/ctwiguna/gudangku |
| Live application | https://43.173.41.195.sslip.io |
| Video presentasi | [tempel tautan video] |
| Database | MySQL 8.0.46, ekspor di `database/gudangku.sql` |

## 3. Ringkasan fitur

1. Autentikasi manual dua role (owner, manajer outlet) dengan aktivasi akun oleh owner.
2. Penerimaan barang oleh manajer tanpa harga, lalu pelengkapan harga oleh owner.
3. Hutang supplier, penandaan pembayaran, dan pembatalan penerimaan.
4. Stock opname mingguan dengan penyesuaian stok otomatis.
5. Ledger stok sebagai sumber saldo dan bukti audit.
6. CRUD pengeluaran operasional enam kategori.
7. CRUD produk, kategori, supplier, outlet, dan user.
8. Dashboard berbeda per role dan laporan owner per periode.
9. Keamanan: CSRF, XSS, SQL injection, RBAC, rate limit, penyaringan field per role.

## 4. Jawaban esai (ringkas)

Ringkasan ini bisa dipakai sebagai naskah pendukung video; versi lengkap ada di `docs/SKRIP_VIDEO.md`.

**a. Alur data.** Browser → route Laravel → middleware auth/role → controller → Eloquent/service → props Inertia → React. Setelah halaman pertama, komunikasi memakai JSON Inertia. Semua validasi, otorisasi, dan penulisan data tetap di server.

**b. Penerapan MVC.** Model di `app/Models`, view di `resources/js/Pages`, controller di `app/Http/Controllers`, ditambah FormRequest untuk validasi, Policy/middleware untuk otorisasi, dan Service untuk logika stok/pembelian/opname. React hanya lapisan tampilan.

**c. Struktur database.** 11 tabel relasional. Saldo stok dihitung dari `stock_movements` (`SUM(quantity)` per outlet dan produk), bukan kolom tersimpan, supaya tidak ada dua sumber kebenaran. `quantity` bertanda; FK `RESTRICT` untuk riwayat, `SET NULL` untuk supplier, `CASCADE` untuk item; `UNIQUE(outlet_id, week_start)` untuk aturan opname mingguan.

**d. Logika backend.** `StockService` adalah satu-satunya pintu tulis ledger dan menolak saldo negatif. Penerimaan, pelengkapan harga, dan opname berjalan dalam transaksi database. Total pembelian dihitung ulang di server; angka dari klien diabaikan.

**e. Penanganan error.** Validasi memakai pesan yang menjelaskan cara memperbaiki. Tiga bug nyata yang ditemukan dan diperbaiki: `HAVING` tidak portabel ke SQLite, pembacaan payload Inertia di test, dan `APP_TIMEZONE` yang tidak terbaca karena `config/app.php` hardcoded UTC. Ditambah satu kasus error yang menutupi error (permission `storage/logs`).

**f. Keamanan.** CSRF (419 tanpa token), escaping React tanpa `dangerouslySetInnerHTML`, Eloquent binding, rate limit login, RBAC middleware + policy, scoping outlet di query, penyaringan harga di controller, `role`/`is_active` tidak fillable, session regenerate/invalidate, cookie `Secure`+`HttpOnly`, header keamanan termasuk HSTS, serta `composer audit` dan `npm audit` bersih.

**g. Deployment.** VPS Ubuntu 24.04, nginx + PHP 8.5.12 FPM, MySQL 8, Node 22, Let's Encrypt. Empat masalah deploy nyata beserta solusinya dicatat di `docs/DEPLOY_VPS.md` (blokir `api.launchpad.net`, paket `php8.5-opcache`, urutan drop-in sshd, keyboard VNC).

## 5. Deliverables checklist

| Deliverable | Berkas / tautan | Status |
| :--- | :--- | :---: |
| Source code + commit history | https://github.com/ctwiguna/gudangku | selesai |
| File database `.sql` | `database/gudangku.sql` | selesai |
| `.env.example` | `.env.example` | selesai |
| README (instalasi, akun, role) | `README.md` | selesai |
| Live URL | https://43.173.41.195.sslip.io | selesai |
| PDF presentasi | `docs/PRESENTASI.md` + `docs/screenshots/` | materi siap, perlu ekspor |
| Video esai | `docs/SKRIP_VIDEO.md` | naskah siap, perlu rekam |
| Catatan uji DoD | `docs/UJI.md` | selesai |
| Panduan deploy | `docs/DEPLOY_VPS.md` | selesai |

## 6. Akun demo

| Peran | Email | Password |
| :--- | :--- | :--- |
| Owner | `owner@gudangku.test` | `password` |
| Manajer | `ina@gudangku.test` | `password` | Outlet Kedungmundu (KDM-01) |
| Manajer | `salsi@gudangku.test` | `password` | Outlet Tembalang (TMB-01) |
| Manajer | `nafa@gudangku.test` | `password` | Outlet Alton (ALT-01) |
| Manajer nonaktif | `kandidat@gudangku.test` | `password` | Belum punya outlet |

## 7. Catatan untuk penilai

1. Server memakai MySQL 8.0.46, bukan 26.10.0 seperti di PRD; PHP 8.5.12 terpenuhi persis.
2. Domain memakai `sslip.io` karena keterbatasan waktu.
3. `php artisan serve` dipakai di belakang nginx untuk demo; untuk produksi nyata, ganti ke PHP-FPM (sudah terpasang) atau FrankenPHP.
