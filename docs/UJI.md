# Catatan Uji — Definisi Selesai

Merujuk `PRD_gudangku.md` bagian 16. Setiap butir punya bukti: nama test otomatis, verifikasi live, atau pemeriksaan database.

- Tanggal uji: 28 Oktober 2026
- Lingkungan live: https://43.173.41.195.sslip.io (PHP 8.5.12, MySQL 8.0.46, nginx 1.24.0)
- Test otomatis: `php artisan test` → **176 passed, 754 assertions**
- Audit: `composer audit` bersih, `npm audit` 0 vulnerability

| No | Butir | Status | Bukti |
| :--- | :--- | :--- | :--- |
| 1 | Register membuat akun manajer nonaktif dan belum bisa login sebelum owner mengaktifkan dan menugaskan outlet | Lulus | `AuthTest::test_register_creates_inactive_manager_and_ignores_role_input`, `test_inactive_account_cannot_login`, `UserTest::test_owner_can_activate_pending_account` |
| 2 | Register tidak bisa membuat owner, form register tanpa field role | Lulus | `AuthTest::test_register_creates_inactive_manager_and_ignores_role_input` (mengirim `role=owner` tetap manajer), `SecurityTest::test_register_cannot_escalate_role` |
| 3 | Login salah password lima kali menghasilkan penolakan sementara | Lulus | `AuthTest::test_login_is_rate_limited_after_five_failed_attempts`; live: percobaan ke-6 menampilkan "Terlalu banyak percobaan login" |
| 4 | Logout menghapus session, tombol back tidak membuka halaman ber-login | Lulus | `AuthTest::test_logout_invalidates_session`; live: `POST /logout` → `/dashboard` berikutnya 302 ke `/login` |
| 5 | Owner bisa membuat outlet baru dan user manajer untuk outlet itu | Lulus | `OutletTest::test_owner_can_create_outlet`, `UserTest::test_owner_can_create_manager_with_outlet`; live: user #5 dibuat lewat form lalu login berhasil |
| 6 | Manajer hanya melihat stok, penerimaan, dan opname outlet sendiri; URL outlet lain 403 | Lulus | `ReceiptTest::test_manager_cannot_view_other_outlet_receipt`, `StockOpnameTest::test_history_is_scoped_for_manager`, `ProductTest::test_manager_stock_is_scoped_to_own_outlet`, `SecurityTest::test_manager_cannot_open_other_outlet_receipt_by_id`; live: `/receipts/3` (cabang) → 403 |
| 7 | Manajer membuka pengeluaran, laporan, pembelian, atau pengaturan → 403 | Lulus | `ExpenseTest::test_manager_is_forbidden_on_all_expense_routes`, `PurchaseTest::test_manager_is_forbidden_on_purchase_routes`, `ReportTest::test_manager_is_forbidden`, `OutletTest::test_manager_is_forbidden_on_outlet_routes`, `UserTest::test_manager_is_forbidden_on_user_routes`; live: `/reports`, `/outlets`, `/users`, `/settings` → 403 |
| 8 | Props Inertia manajer tidak berisi price_buy, price, subtotal, total, amount, nilai laporan | Lulus | `SecurityTest::test_manager_pages_never_expose_money_keys` memindai 7 route manajer secara rekursif; `DashboardTest::test_manager_dashboard_props_contain_no_money_fields`; live: 0 kemunculan `price_buy`/`amount`/`subtotal`/`debt_total` di `/products`, `/receipts`, `/receipts/{id}` |
| 9 | Owner input harga produk per pcs dan melihat harga/total di detail pembelian; manajer tidak melihat field harga | Lulus | `ProductTest::test_owner_product_list_includes_price`, `test_manager_product_list_has_no_price_field`, `ReceiptTest::test_owner_receipt_detail_includes_price_fields`, `test_manager_receipt_detail_has_no_price_fields`; live: owner `price_buy` ada, manajer 0 kemunculan |
| 10 | Form tanpa token CSRF menghasilkan 419 | Lulus | `SecurityTest::test_request_without_csrf_token_is_rejected`; live: `POST /login` tanpa token → **419** |
| 11 | Catatan berisi tag script tampil sebagai teks biasa | Lulus | `SecurityTest::test_script_tag_in_note_is_stored_as_plain_text`, `test_frontend_never_uses_dangerously_set_inner_html` (pemindaian seluruh `.jsx`) |
| 12 | Percobaan injeksi di filter tidak mengubah query dan tidak menampilkan error database | Lulus | `SecurityTest::test_sql_injection_attempt_in_month_filter_is_neutralized` (`month=2026-10' OR '1'='1` → fallback bulan berjalan, total tetap sama dengan DB) |
| 13 | Transaksi yang mengurangi stok melebihi saldo ditolak dengan pesan jelas | Lulus | `ReceiptTest::test_stock_service_rejects_movement_that_makes_balance_negative`, `test_removing_item_is_rejected_when_stock_would_go_negative`; pesan menyebut saldo dan jumlah permintaan |
| 14 | Penerimaan 10 qty menaikkan stok 10 dan menulis satu movement in | Lulus | `ReceiptTest::test_manager_can_record_receipt_without_price` (qty +10, tepat 1 movement `in`); live: penerimaan 3 pcs `SLT-01` → saldo 22 → 25, movement #13 `in +3` |
| 15 | Penerimaan manajer tersimpan dengan priced_at kosong dan total 0 | Lulus | `ReceiptTest::test_manager_can_record_receipt_without_price`; live: purchase #6 `priced_at NULL`, `total 0.00`, item `price NULL` |
| 16 | Setelah owner melengkapi semua harga, priced_at terisi dan pembelian masuk hitungan hutang | Lulus | `PurchaseTest::test_owner_can_fill_all_prices_and_purchase_becomes_priced`; live: purchase #6 `priced_at 2026-10-28`, `total 15.000`, hutang naik |
| 17 | Tandai pembayaran menghilangkan nilai dari total hutang | Lulus | `PurchaseTest::test_owner_can_mark_paid_and_debt_drops`; live: hutang 507.000 → 492.000 setelah `paid_at` terisi |
| 18 | Pengingat opname muncul di dashboard manajer dan badge navigasi | Lulus | `DashboardTest::test_manager_opname_reminder_follows_outlet_compliance`; live: manajer cabang `"opname_due":true`, banner tampil, badge nav aktif |
| 19 | Setelah opname disimpan, pengingat hilang dan stok sama dengan jumlah hitung | Lulus | `StockOpnameTest::test_reminder_disappears_after_opname`, `test_manager_can_submit_opname_and_stock_equals_counted`; live: `opname_due` false, `BTL-PRF` 30 → 28 |
| 20 | Selisih opname menulis movement adjustment; selisih nol tidak menulis movement | Lulus | `StockOpnameTest::test_only_non_zero_differences_write_adjustment_movements` (2 movement untuk 2 produk berselisih); live: hanya `BTL-PRF -2` yang tercatat |
| 21 | Opname kedua di minggu yang sama ditolak | Lulus | `StockOpnameTest::test_second_opname_in_same_week_is_rejected`; live: jumlah opname per outlet tetap 1 setelah percobaan kedua |
| 22 | Dashboard owner menampilkan status opname per outlet untuk minggu berjalan | Lulus | `DashboardTest::test_outlet_status_marks_opname_per_outlet`; live: `CKR-01` sudah, `CKR-02` belum |
| 23 | Total pengeluaran bulan ini di dashboard sama dengan query langsung ke database | Lulus | `DashboardTest::test_owner_dashboard_numbers_match_database`; live: `9.675.000` di props = `9.675.000,00` di SQL |
| 24 | Produk di bawah minimum muncul di dashboard dan halaman produk | Lulus | `DashboardTest::test_below_min_products_are_listed_for_owner`, `ProductTest` (panel "Perlu restock"); live: `belowMin` 10 baris |
| 25 | Semua form store dan update memakai FormRequest | Lulus | 14 FormRequest di `app/Http/Requests`; route store/update tidak menerima `Request` biasa |
| 26 | Semua state form bagian 9 muncul di kode (default, hover, focus, filled, error, disabled, loading, success, read-only) | Lulus | `resources/css/app.css` (`.field--error`, `:disabled`, `[readonly]`, `.btn__spinner`), `Button` (processing/disabled), `FormField` (error + `aria-describedby`), `Toast` (success/error), 5 halaman form dengan ringkasan error |
| 27 | Build produksi React berhasil tanpa error dan tanpa peringatan kritis | Lulus | `npm run build` → selesai tanpa error; ukuran `app-*.js` 449,89 kB / 128,82 kB gzip |
| 28 | Refresh di route dalam tidak menghasilkan 404 | Lulus | `app.jsx` memakai `import.meta.glob` eager; live: refresh `/products`, `/receipts`, `/stock-opnames` → 200 |
| 29 | Live URL bisa dibuka, login owner dan manajer berhasil, data contoh tampil | Lulus | https://43.173.41.195.sslip.io — owner dan manajer login 200; dashboard menampilkan angka contoh; error log server 0 |
| 30 | Plastik packing kecil dan besar muncul sebagai dua kategori dan dua produk terpisah | Lulus | Seeder `CatalogSeeder`; live `/master-data` menampilkan dua kategori berbeda, `/products` dua produk (`PLK-KCL`, `PLK-BSR`) |
| 31 | ERD, flowchart, struktur directory, struktur MVC, dan rincian hak akses ada di PRD dan PDF | Lulus | `PRD_gudangku.md` §4.1–4.4, §6.4, §10.1, §8.3–8.4; `README.md` struktur direktori; PDF presentasi (D4.5) |
| 32 | Sesi tidak menyimpan kredensial di localStorage dan cookie sesi tidak bisa dibaca JavaScript | Lulus | 0 kemunculan `localStorage`/`sessionStorage`; cookie `gudangku-session` `HttpOnly; Secure; SameSite=Lax`; `SessionSecurityTest::test_session_configuration_is_hardened` |
| 33 | Absolute timeout memaksa login ulang berapa pun aktivitasnya | Lulus | `SessionSecurityTest::test_absolute_timeout_forces_logout`, `test_expired_session_also_blocks_owner_only_routes` |
| 34 | Ganti password dan penonaktifan user mencabut sesi di perangkat lain | Lulus | `SessionSecurityTest::test_changing_own_password_revokes_other_sessions`, `test_owner_resetting_user_password_revokes_that_users_sessions`, `test_deactivating_user_revokes_their_sessions` |
| 35 | Ekspor Excel pengeluaran per outlet per bulan berisi angka yang sama dengan database, dan hanya boleh diunduh owner | Lulus | `ExpenseExportTest` (8 test): berkas `.xlsx` valid (magic bytes `PK`, dibaca ulang dengan PhpSpreadsheet), **4 sheet** (Ringkasan, Per Kategori, Pembelian Produk, Rincian), pengeluaran per bulan per kategori dengan subtotal bulanan + total tahunan per outlet cocok dengan DB, pembelian per kategori produk (Gas LPG) cocok dengan DB, manajer 403, tahun tidak valid jatuh ke tahun berjalan |
| 36 | Menu Gudang tersedia untuk owner (bottom navigation) dan memuat aksi tambah outlet serta tambah kategori barang | Lulus | `WarehouseTest` (5 test): owner `/warehouse` 200, manajer 403, tamu 302, `/master-data?new=1` menghasilkan `openCreate=true`, `/outlets?new=1` menghasilkan `openCreate=true` |
| 37 | Database Supplier menyimpan informasi lengkap pemasok: kontak, alamat, lead time, dan term of payment dalam hari | Lulus | `SupplierTest` (9 test): halaman memuat field lengkap, simpan/ubah lead time + TOP angka hari, TOP berupa teks (`NET 30`) ditolak dengan pesan "Term of payment harus berupa angka hari", lead time di luar 0–365 ditolak, supplier yang dipakai pembelian tidak bisa dihapus, manajer 403. Bukti live: kolom TOP menampilkan `30 hari` dan `14 hari`; `input[type=number]` menolak teks di sisi klien dan server tetap menolak walau dikirim manual |
| 38 | Harga Barang memuat kode, nama, satuan (pcs/dus/kg), ID supplier, harga satuan, dan tanggal update harga | Lulus | `ProductTest` + `ProductController`: satuan dibatasi `pcs/dus/kg`, `price_updated_at` diisi server saat barang dibuat dan diperbarui hanya kalau harga berubah |
| 39 | Kelola Karyawan menyimpan nama, gaji, dan satuan gaji (harian/mingguan/bulanan) | Lulus | `EmployeeTest` (8 test): halaman + 3 pilihan satuan gaji, simpan/ubah, satuan gaji tidak dikenal ditolak, gaji negatif ditolak, toggle status, hapus, manajer 403 |

## Uji manual tambahan di live

| Uji | Hasil |
| :--- | :--- |
| `POST /login` tanpa token CSRF | 419 |
| Manajer membuka data outlet lain (`/receipts/3`) | 403 |
| Owner mengubah profil dengan `role=owner` | 302, tetapi database tetap `manajer` |
| Halaman 403/404 | dirender React (`Errors/403`, `Errors/404`), status asli dipertahankan |
| `composer audit` | bersih setelah `symfony/mime` dinaikkan ke v8.1.10 (CVE-2026-45070) |
| `npm audit` | 0 vulnerability |

## Catatan metode

1. Bukti penolakan akses diambil dari **status HTTP dan isi database**, bukan dari teks halaman. Alasannya: props Inertia dirender sebagai JSON ter-escape di `<script type="application/json">`, sehingga pencarian teks di HTML tidak selalu konklusif.
2. Setiap angka dashboard dan laporan dibandingkan dengan query SQL langsung pada periode yang sama.
3. Saldo stok selalu diverifikasi terhadap jumlah kolom `quantity` di `stock_movements`, bukan terhadap angka yang ditampilkan aplikasi.
