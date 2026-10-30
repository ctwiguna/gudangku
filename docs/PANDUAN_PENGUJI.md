# Panduan Penguji — gudangku

Panduan singkat untuk dosen/penguji UAS **Web Application Development**. Isinya: akun yang bisa dipakai, alur uji tercepat, daftar periksa per halaman, dan hal-hal yang perlu diketahui saat menguji.

| Item | Isi |
| :--- | :--- |
| Aplikasi | **gudangku** — Catat, monitor dan audit stok + Pengeluaran bisnis |
| Alamat live | https://43.173.41.195.sslip.io |
| Repository | https://github.com/ctwiguna/gudangku |
| Mahasiswa | Chandra TW — NIM 25110300017 — Universitas Cakrawala |
| Teknologi | Laravel 13, Inertia.js, React 19, MySQL 8, PHP 8.5.12 |
| Dokumen diperbarui | 30 Oktober 2026 |

## 1. Akun pengujian

Semua akun memakai password yang sama: **`hidupberkah`** (huruf kecil semua).

| Peran | Nama | Email | Password | Outlet |
| :--- | :--- | :--- | :--- | :--- |
| Owner | Chandra TW | `owner@gudangku.test` | `hidupberkah` | semua outlet |
| Manajer | Ina | `manajer1@gudangku.test` | `hidupberkah` | Kedungmundu (KDM-01) |
| Manajer | Nafa | `manajer2@gudangku.test` | `hidupberkah` | Alton (ALT-01) |
| Manajer | Salsi | `manajer3@gudangku.test` | `hidupberkah` | Tembalang (TMB-01) |

Yang perlu diketahui:

- Percobaan login dibatasi **5 kali per menit**. Jika muncul "Terlalu banyak percobaan login", tunggu satu menit lalu coba lagi.
- Sesi otomatis berakhir setelah **60 menit tanpa aktivitas**, **12 jam sejak login**, atau ketika browser ditutup. Ini disengaja karena aplikasi dipakai di komputer bersama dalam outlet.
- Mohon jangan mengganti password akun demo agar penguji berikutnya tetap bisa masuk.
- Aplikasi nyaman diuji dari ponsel maupun desktop; navigasi bawah menyesuaikan peran yang sedang login.

## 2. Kondisi data saat dokumen ini dibuat

- 3 outlet, 14 karyawan, 15 supplier, dan 6 produk sudah terisi.
- Transaksi (penerimaan barang, pengeluaran, stock opname) belum dibuat, sehingga sebagian angka dashboard masih nol. Bagian 3 memandu Anda membuatnya sekaligus menguji alurnya. Jika saat Anda menguji transaksi sudah ada, alur di bagian 3 tetap bisa dijalankan untuk melihat semuanya bekerja.

## 3. Alur uji cepat (± 10 menit)

Ikuti berurutan. Setiap langkah menyebutkan peran yang dipakai.

### 3.1 Owner — dashboard dan master data

1. Buka https://43.173.41.195.sslip.io, login dengan `owner@gudangku.test` / `hidupberkah`.
2. **Dashboard** menampilkan kartu pengeluaran bulan ini, hutang supplier, antrean pembelian belum berharga, nilai stok, produk di bawah minimum, dan status opname tiap outlet. Kartu **Hutang supplier** bisa diklik menuju rinciannya.
3. Buka menu **Gudang** (slot tengah bawah): empat kartu besar — Kelola Outlet, Database Supplier, Harga Barang, Kelola Karyawan — semuanya bisa diklik.
4. Cek **Database Supplier** (kontak, alamat, lead time, TOP) dan **Harga Barang** (satuan, supplier, harga per pcs, tanggal update harga).

### 3.2 Owner — mencatat pengeluaran dan mengekspor laporan

5. Buka tombol tengah **Catat**. Coba tekan Simpan dengan form kosong: muncul pesan kesalahan di tiap kolom.
6. Isi kategori (mis. Sewa), tanggal, dan nominal, lalu simpan. Kategori **Gaji** memunculkan kolom periode minggu yang wajib diisi.
7. Buka **Laporan**, pilih bulan berjalan, lalu tekan **Unduh Excel**. Berkas `.xlsx` berisi 4 sheet: Ringkasan, Per Kategori, Pembelian Produk, dan Rincian.

### 3.3 Manajer — menerima barang dan stock opname

8. Keluar, lalu login dengan `manajer1@gudangku.test` / `hidupberkah`. Perhatikan menu bawah berubah (Dashboard, Stok, Terima, Opname, Akun) dan dashboard tidak menampilkan angka uang.
9. Coba buka `https://43.173.41.195.sslip.io/expenses` langsung: muncul halaman **403** karena halaman keuangan hanya untuk owner.
10. Buka **Terima** (slot tengah): pilih supplier, tanggal terima, produk, dan jumlah. Form sengaja tidak punya kolom harga — harga diisi owner. Simpan; stok produk bertambah.
11. Buka **Opname**: isi jumlah fisik tiap produk, lalu **Simpan opname**. Selisih otomatis menyesuaikan stok dan penyesuaiannya tercatat di **Riwayat stok**. Satu outlet hanya boleh satu opname per minggu.

### 3.4 Owner — melengkapi harga, membayar hutang, dan uji register

12. Login kembali sebagai owner, buka **Pembelian** (atau kartu antrean di dashboard). Baris berstatus **Menunggu harga** → **Isi harga** → simpan. Setelah semua item terisi, status berubah **Sudah dihargai** dan nilainya masuk hitungan hutang supplier.
13. Di halaman yang sama, tekan **Tandai dibayar**: nilainya hilang dari total hutang (cek lagi kartu Hutang supplier / halaman **Hutang Supplier**).
14. Uji pendaftaran: keluar, buka **Daftar sebagai manajer outlet**, isi nama/email/password. Akun yang terbentuk **tidak bisa login** sebelum owner mengaktifkannya. Login owner → **Pengaturan → User → Ubah** akun tersebut: aktifkan dan pilih outlet, lalu akun itu bisa login.

## 4. Daftar periksa per halaman

| Halaman | Login sebagai | Yang diperiksa |
| :--- | :--- | :--- |
| Dashboard | owner | Kartu pengeluaran bulan ini, hutang supplier (bisa diklik), antrean harga, nilai stok, produk di bawah minimum, status opname per outlet |
| Dashboard | manajer | Stok outlet sendiri, produk di bawah minimum, pengingat opname, penerimaan terakhir — tanpa angka uang |
| Gudang | owner | Empat kartu yang seluruhnya bisa diklik |
| Database Supplier | owner | Tambah/ubah/hapus; kontak, alamat, lead time (hari), TOP (hari) |
| Harga Barang / Stok | owner / manajer | Owner melihat harga per pcs; manajer tidak melihat harga sama sekali |
| Terima barang | manajer | Form tanpa harga; stok bertambah; muncul di daftar penerimaan |
| Pembelian | owner | Antrean menunggu harga, isi harga, tandai dibayar, hapus penerimaan yang belum berharga |
| Hutang Supplier | owner | Rincian barang per supplier, subtotal, tandai dibayar |
| Opname | manajer | Satu opname per minggu; selisih menyesuaikan stok; riwayat 8 minggu |
| Riwayat stok | keduanya | Ledger: penerimaan (masuk) dan penyesuaian opname |
| Pengeluaran | owner | Enam kategori; periode wajib untuk gaji; edit dan hapus |
| Laporan | owner | Rekap per kategori dan outlet; Unduh Excel 4 sheet |
| Pengaturan | owner | Pintasan ke outlet, user, kategori & supplier, produk, laporan, riwayat stok |
| Profil | keduanya | Ganti nama/email; ganti password wajib mengisi password lama |

## 5. Uji keamanan yang bisa dicoba

| Uji | Cara | Hasil yang diharapkan |
| :--- | :--- | :--- |
| Batas peran | Login manajer, buka `/expenses`, `/users`, `/reports`, `/outlets`, `/purchases` | Halaman 403 rancangan aplikasi |
| Harga tidak bocor | Login manajer, buka **Stok** dan **Terima barang** | Tidak ada harga beli/harga per pcs di layar |
| XSS | Owner → Pengeluaran → Catat → isi Catatan dengan `<script>alert(1)</script>` lalu simpan | Teks tampil apa adanya, tidak ada popup |
| CSRF (teknis) | Kirim `POST /login` tanpa token | 419 |
| Rate limit | Login dengan password salah 5 kali | "Terlalu banyak percobaan login"; tunggu 1 menit |
| Register aman | Daftar akun baru | Selalu jadi manajer nonaktif; tidak bisa login sebelum diaktivasi owner |

## 6. Jika mengalami kendala

| Gejala | Penyebab & solusi |
| :--- | :--- |
| "Terlalu banyak percobaan login" | Rate limit 5 kali/menit — tunggu 1 menit, lalu coba lagi |
| Tiba-tiba kembali ke halaman login | Sesi berakhir (60 menit idle / 12 jam absolut / browser ditutup) — login ulang |
| Stok terlihat 0 | Stok dihitung dari riwayat penerimaan dan opname; buat penerimaan barang dulu |
| Halaman tampak tidak berubah | Lakukan refresh penuh (Ctrl/Cmd+Shift+R) |
| Tombol/label berbeda dari dokumen | Pastikan login dengan peran yang sesuai — menu owner dan manajer memang berbeda |

## 7. Dokumen pendukung

- `README.md` — gambaran fitur, stack, dan cara instalasi.
- `docs/UJI.md` — 40 butir uji manual beserta buktinya.
- `docs/PRESENTASI.md` — materi presentasi (ERD, flowchart, dan lain-lain).
- `docs/DEPLOY_VPS.md` — catatan deploy di VPS.
- `docs/SKRIP_VIDEO.md` — naskah video esai.
