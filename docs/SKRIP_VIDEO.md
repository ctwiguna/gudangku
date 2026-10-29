# Skrip Video Esai — gudangku

Durasi target 9–11 menit. Setiap segmen menjawab satu pertanyaan rubrik. Bagian dalam tanda kurung adalah aksi di layar.

Kalimat pembuka yang disarankan:

> "Nama saya Chandra TW, NIM 25110300017. Project saya gudangku, aplikasi pencatatan dan stock opname inventaris outlet, dibangun dengan Laravel 13, Inertia.js, React 19, dan MySQL. Aplikasi live di 43.173.41.195.sslip.io dan source code-nya ada di GitHub."

## Segmen 1 — Demo singkat (1,5 menit)

(Tunjukkan halaman login, masuk sebagai manajer.)

1. Login manajer pusat → dashboard stok outlet sendiri, ada tombol Terima, Opname, dan badge pengingat.
2. Buka **Terima barang** → tunjukkan form hanya berisi supplier, tanggal, produk, dan jumlah. **Tidak ada field harga.** Simpan.
3. Buka **Opname** → tunjukkan stok sistem per produk dan kolom jumlah hitung; selisih muncul otomatis per baris.

Kalimat kunci:

> "Perhatikan, di seluruh layar manajer tidak ada satu pun angka rupiah. Itu bukan sekadar disembunyikan di tampilan — server memang tidak mengirimkannya."

(Logout, masuk sebagai owner.)

4. Buka **Pembelian & harga** → antrean menunggu harga dari manajer tadi.
5. Isi harga per item → total terhitung, hutang bertambah.
6. Tandai dibayar → hutang berkurang.
7. Buka **Laporan** dan **Riwayat stok**.

## Segmen 2 — Alur data (2 menit)

(Tunjukkan `routes/web.php`, lalu `HandleInertiaRequests`, lalu props di Network tab.)

Poin yang dijelaskan:

1. Browser membuka URL → Laravel route + middleware `auth`/`owner` berjalan lebih dulu.
2. Controller memanggil Eloquent atau service, lalu `Inertia::render('Halaman', [...props])`.
3. Respons pertama berisi HTML dengan data halaman di dalam `<script type="application/json">`; setelah itu React mengambil alih dan request berikutnya hanya JSON, bukan halaman penuh.
4. Form React memakai `useForm` dari Inertia; submit mengirim ke route Laravel yang sama. Validasi, otorisasi, dan simpan tetap di server.
5. Bukti di Network tab: props manajer **tidak** memuat `price_buy`, `subtotal`, `amount`, `total`. Ini yang membedakan penyaringan server dari penyembunyian di UI.

Kalimat kunci:

> "React hanya merender. Semua keputusan ada di server, jadi memanipulasi tampilan di browser tidak membuka data yang tidak dikirim."

## Segmen 3 — Penerapan MVC (1,5 menit)

(Tunjukkan tabel lapisan, buka satu file per lapisan.)

| Lapisan | File yang dibuka | Yang ditunjukkan |
| :--- | :--- | :--- |
| Routing | `routes/web.php` | grup `middleware(['auth','owner'])` |
| Controller | `app/Http/Controllers/ReceiptController.php` | `store()` menulis purchase, item, movement dalam satu `DB::transaction` |
| Validasi | `app/Http/Requests/StoreReceiptRequest.php` | tidak ada aturan harga; qty wajib ≥ 1 |
| Otorisasi | `app/Policies/PurchasePolicy.php` | manajer hanya outlet sendiri dan hanya sebelum dihargai |
| Service | `app/Services/StockService.php` | satu pintu tulis ledger + penjagaan saldo |
| Model | `app/Models/Product.php` | `stockIn()`, `belowMinStock()` dari ledger |
| View | `resources/js/Pages/Products/Index.jsx` | kolom harga hanya bila `canManage` |

Kalimat kunci:

> "React bukan pelanggaran MVC. Model, validasi, otorisasi, dan logika bisnis tetap di server; React hanya lapisan view yang lebih interaktif."

## Segmen 4 — Database (2 menit)

(Tunjukkan ERD di PDF, lalu `SHOW CREATE TABLE` singkat.)

1. **11 tabel** dengan relasi jelas: outlets, users, categories, suppliers, products, purchases, purchase_items, stock_movements, stock_opnames, stock_opname_items, expenses.
2. Keputusan penting: **saldo stok tidak disimpan di kolom produk.** Saldo = `SUM(quantity)` pada `stock_movements`. Alasannya, kalau saldo disimpan terpisah dari riwayat, keduanya bisa berbeda dan tidak ada cara memutuskan mana yang benar. Dengan ledger, saldo selalu bisa diaudit sampai baris pergerakan asalnya.
3. `quantity` **bertanda**: positif barang masuk, negatif barang keluar, dan selisih opname memakai tanda yang sama.
4. Aturan foreign key: `RESTRICT` untuk riwayat (pembelian, movement, opname, pengeluaran), `SET NULL` untuk supplier pada produk, `CASCADE` untuk item yang mengikuti dokumennya.
5. `UNIQUE (outlet_id, week_start)` pada `stock_opnames` memaksa aturan "satu opname per outlet per minggu" di level database, bukan hanya di kode.
6. Bukti audit: `SELECT SUM(quantity) FROM stock_movements WHERE outlet_id=? AND product_id=?` sama dengan angka stok di aplikasi.

## Segmen 5 — Logika backend (2 menit)

(Tunjukkan `StockService`, `StockOpnameService`, `PurchaseController`.)

1. **Satu pintu tulis stok.** Setiap perubahan stok lewat `StockService::record()`: menolak quantity nol, dan untuk quantity negatif memeriksa saldo berjalan. Pesan errornya menyebut angka: *"Stok tidak cukup. Saldo saat ini 96, permintaan 100."*
2. **Transaksi.** Penerimaan menulis `purchases` + `purchase_items` + `stock_movements` dalam satu `DB::transaction`. Kalau satu gagal, semua batal.
3. **Pemisahan wewenang uang dan stok.** Manajer mencatat jumlah; record dibuat dengan `priced_at = NULL` dan `total = 0`. Owner mengisi harga; setelah semua item berharga, `priced_at` diisi dan total dihitung ulang **di server**. Klien yang mengirim `total` palsu diabaikan — ini ada test-nya.
4. **Opname.** Stok sistem dihitung ulang saat submit, bukan memakai angka di form. Selisih nol tidak menulis movement; selisih tidak nol menulis `adjustment`. Setelah itu stok sama dengan jumlah hitung.
5. **Pembatalan penerimaan.** Hanya untuk pembelian belum berharga dan belum dibayar; sistem menulis movement keluar sebesar jumlah item sehingga saldo kembali. Kalau saldo tidak cukup (misalnya karena selisih opname), pembatalan ditolak.

## Segmen 6 — Penanganan error dan bug nyata (2 menit)

(Tunjukkan 3 hal ini; ini bagian yang paling menunjukkan proses, bukan hanya hasil.)

1. **`HAVING clause on a non-aggregate query`.** Scope `belowMinStock` awalnya memakai `HAVING` pada alias. MySQL mengizinkan, **SQLite menolak** sehingga test mengembalikan 500. Perbaikan: subquery berkorelasi dengan parameter binding. Pelajaran: test suite menyelamatkan bug yang tidak terlihat di server produksi.
2. **`viewData('page')` bukan array di Inertia 3.5.** Helper `assertInertia` gagal dengan pesan menyesatkan *"Not a valid Inertia response"*. Saya baca payload dengan cara toleran terhadap dua bentuk. Pelajaran: baca pesan error lengkap, jangan menebak dari stack trace teratas.
3. **`APP_TIMEZONE` tidak pernah dipakai.** Laravel menyimpan `'timezone' => 'UTC'` hardcoded di `config/app.php`. Server berjalan UTC padahal PRD meminta `Asia/Jakarta`. Perbaikan: `env('APP_TIMEZONE', 'UTC')` lalu bangun ulang config cache. Ini penting karena minggu opname dan pengelompokan "bulan ini" bergantung waktu.
4. **Error yang menutupi error.** Saat seeder gagal di server, exception permission `storage/logs` justru menyembunyikan error SQL aslinya. Perbaikan: `chown ubuntu:www-data` dan setgid pada direktori storage.

Kalimat kunci:

> "Tiga bug ini bukan di logika bisnis, tapi semuanya bisa membuat aplikasi tampak benar padahal salah. Yang saya pelajari: verifikasi ke database dan baca pesan error asli, jangan percaya tampilan."

## Segmen 7 — Keamanan (2 menit)

(Tunjukkan bukti singkat per poin.)

| Kontrol | Bukti |
| :--- | :--- |
| CSRF | POST tanpa token → **419** (diuji, dan dibuktikan di live) |
| XSS | catatan `<script>` tersimpan sebagai teks; tidak ada `dangerouslySetInnerHTML` di seluruh komponen |
| SQL injection | filter `month=2026-10' OR '1'='1` jatuh ke bulan berjalan, tidak ada error database |
| RBAC | middleware `EnsureOwner`/`EnsureManager` + 7 policy per model |
| Scoping outlet | manajer membuka penerimaan outlet lain → **403** (diuji dan di live) |
| Otorisasi field | props manajer dipindai di 7 route: tidak ada `price`, `amount`, `total`, `debt`, `stock_value` |
| Rate limit | 5 percobaan login per menit, percobaan keenam ditolak |
| Mass assignment | `role`/`is_active` tidak fillable; register mengirim `role=owner` tetap manajer |
| Session | regenerate saat login, invalidate + regenerate token saat logout |
| Password | bcrypt, minimal 8 karakter, ganti password wajib password lama |
| Dependency | `composer audit` menemukan CVE-2026-45070 di `symfony/mime`, dipatch ke v8.1.10; `npm audit` 0 |
| Header | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `HSTS`, cookie `Secure`+`HttpOnly` |

Kalimat kunci:

> "Penyaringan harga saya lakukan di controller, bukan di React. Alasannya sederhana: props Inertia bisa dibaca di Network tab, jadi menyembunyikan kolom di tampilan bukan keamanan."

## Segmen 8 — Deployment dan troubleshooting (2 menit)

(Tunjukkan `docs/DEPLOY_VPS.md` dan terminal server.)

1. Stack server: Ubuntu 24.04, nginx 1.24, PHP 8.5.12 FPM (PPA `ondrej/php`), MySQL 8.0.46, Node 22, Composer, Let's Encrypt.
2. Alur deploy: clone → `composer install --no-dev` → `npm ci && npm run build` → `.env` produksi → `migrate --force` → `config:cache`, `route:cache`, `view:cache` → vhost nginx → sertifikat → verifikasi.
3. Empat masalah nyata dan solusinya:
   - `add-apt-repository` timeout karena `api.launchpad.net` diblokir → PPA ditambahkan manual dengan signing key dari keyserver port 11371.
   - `php8.5-opcache` tidak dipaketkan terpisah → `apt` exit 100, opcache sudah menyatu di CLI/FPM.
   - `PasswordAuthentication no` tidak berlaku karena `50-cloud-init.conf` dievaluasi lebih dulu (sshd first-match-wins) → drop-in `00-` dan verifikasi dengan `sshd -T`.
   - VNC panel bermasalah pemetaan keyboard (`atkbd Unknown key pressed`) → semua penyiapan lewat SSH.
4. Verifikasi akhir: **48 pemeriksaan route × role** (0 menyimpang), logout benar-benar menghapus sesi, refresh route dalam 200, error log 0, dan tampilan mobile diperiksa pada 390px dan 360px tanpa overflow horizontal.

## Segmen 9 — Penutup (30 detik)

> "Yang saya bangun bukan hanya CRUD: ada ledger stok yang bisa diaudit, pemisahan wewenang uang dan stok yang dijaga di server, dan aturan opname mingguan yang dipaksa database. Semua fitur wajib PRD selesai, 133 test lulus, dan aplikasi live bisa dipakai di ponsel. Terima kasih."

## Tips produksi video

1. Rekam dalam satu take per segmen, gabungkan saat edit — lebih tahan salah.
2. Perbesar font editor (16–18 pt) dan terminal agar terbaca di 720p.
3. Pindahkan kursor ke baris kode yang sedang dibicarakan.
4. Jangan membaca skrip kata per kata; hafalkan tiga poin kunci per segmen.
5. Rekam demo live lebih dulu, baru bagian penjelasan kode, supaya kalau ada gangguan jaringan tidak mengulang semuanya.
