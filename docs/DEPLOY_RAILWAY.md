# Langkah Deploy gudangku ke Railway

Dokumen ini dipakai sebagai catatan deploy dan bahan bagian "Deployment Step" pada PDF presentasi.

- Repository: https://github.com/ctwiguna/gudangku
- Builder: Nixpacks (auto-detect PHP + Node)
- Runtime aplikasi: `php artisan serve` di belakang router Railway
- Database: MySQL plugin Railway
- Start command menjalankan migrasi otomatis, lalu cache config/route/view

---

## 1. Prasyarat

```bash
export PATH=/opt/homebrew/bin:$PATH
php -v                      # 8.5.12 (lokal, untuk generate APP_KEY)
railway --version           # 5.71.0
```

## 2. Login dan inisialisasi project

```bash
cd "/Users/ctwiguna/Downloads/First project Laravel/25110300017-ChandraTW-gudangku"
railway login               # membuka browser, sekali saja
railway init                # buat project baru: gudangku
```

## 3. Tambahkan database MySQL

```bash
railway add --database mysql
```

Plugin MySQL menyediakan variabel `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`.

## 4. Set environment variables

Ambil kunci aplikasi (jalankan sekali, simpan hasilnya):

```bash
php artisan key:generate --show
```

Lalu set variabel berikut. Nilai `${{MySQL.xxx}}` adalah referensi variabel Railway, bukan string literal.

| Variabel | Nilai | Catatan |
| :--- | :--- | :--- |
| `APP_NAME` | `gudangku` | |
| `APP_ENV` | `production` | |
| `APP_KEY` | `base64:...` | hasil `key:generate --show` |
| `APP_DEBUG` | `false` | wajib false di produksi |
| `APP_URL` | `https://<domain-railway>` | isi setelah domain jadi |
| `APP_TIMEZONE` | `Asia/Jakarta` | aturan minggu opname |
| `APP_LOCALE` | `id` | |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` | |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` | |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` | |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` | user aplikasi, bukan root |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` | |
| `SESSION_DRIVER` | `database` | tabel `sessions` sudah ada di migrasi |
| `SESSION_SECURE_COOKIE` | `true` | cookie hanya lewat HTTPS |
| `CACHE_STORE` | `database` | |
| `QUEUE_CONNECTION` | `sync` | |
| `LOG_CHANNEL` | `stderr` | log masuk ke Railway log |
| `LOG_LEVEL` | `warning` | |

Lewat CLI:

```bash
railway variables --set "APP_NAME=gudangku" --set "APP_ENV=production" \
  --set "APP_KEY=base64:ISI_HASIL_KEY_GENERATE" --set "APP_DEBUG=false" \
  --set "APP_TIMEZONE=Asia/Jakarta" --set "APP_LOCALE=id" \
  --set "DB_CONNECTION=mysql" --set "DB_HOST=\${{MySQL.MYSQLHOST}}" \
  --set "DB_PORT=\${{MySQL.MYSQLPORT}}" --set "DB_DATABASE=\${{MySQL.MYSQLDATABASE}}" \
  --set "DB_USERNAME=\${{MySQL.MYSQLUSER}}" --set "DB_PASSWORD=\${{MySQL.MYSQLPASSWORD}}" \
  --set "SESSION_DRIVER=database" --set "SESSION_SECURE_COOKIE=true" \
  --set "CACHE_STORE=database" --set "QUEUE_CONNECTION=sync" \
  --set "LOG_CHANNEL=stderr" --set "LOG_LEVEL=warning"
```

## 5. Deploy

```bash
railway up
```

Yang dijalankan Railway (`railway.json`):

- Build: `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
- Start: `php artisan migrate --force && config:cache && route:cache && view:cache && php artisan serve --host=0.0.0.0 --port=$PORT`
- Healthcheck: `GET /up` harus 200

## 6. Buat domain publik

```bash
railway domain
```

Salin domain yang muncul, lalu perbarui `APP_URL` dan deploy ulang:

```bash
railway variables --set "APP_URL=https://DOMAIN_ANDA"
railway up
```

## 7. Isi data contoh (sekali saja)

```bash
railway ssh
# di dalam container:
php artisan db:seed --force
php artisan db:seed --class=DemoSeeder --force   # bila seeder dipisah
exit
```

Bila `railway ssh` tidak tersedia di paket Anda, tambahkan sementara `&& php artisan db:seed --force` ke `startCommand`, deploy, lalu kembalikan.

## 8. Verifikasi setelah live

1. `https://<domain>/up` → 200.
2. Login owner berhasil, dashboard menampilkan angka.
3. Login manajer berhasil, banner opname muncul.
4. Refresh di route dalam (mis. `/expenses`) tidak 404.
5. Buka `/expenses` sebagai manajer → 403.
6. Network tab: props Inertia manajer tidak berisi `price_buy`, `price`, `subtotal`, `total`, `amount`.
7. Catat `php -v` dari `railway ssh` untuk bukti versi PHP platform di README dan PDF.

## 9. Troubleshooting

| Gejala | Penyebab umum | Tindakan |
| :--- | :--- | :--- |
| 500 saat pertama buka | `APP_KEY` kosong | set `APP_KEY`, redeploy |
| Halaman putih + 419 saat submit | `APP_URL` / secure cookie tidak cocok | samakan `APP_URL` dengan domain, `SESSION_SECURE_COOKIE=true` |
| `could not find driver` | ekstensi `pdo_mysql` tidak terpasang di build | tambah `nixpacks.toml` atau pindah ke Dockerfile `php:8.5-cli` + `docker-php-ext-install pdo_mysql` |
| Asset CSS/JS 404 | `npm run build` tidak jalan | pastikan `package-lock.json` ter-commit (`npm ci` butuh lock) |
| 404 saat refresh route dalam | manifest Vite tidak memuat halaman | `app.blade.php` pakai `@vite`, `app.jsx` pakai `import.meta.glob` eager |
| `SQLSTATE[HY000] [2002]` | host DB salah | pakai `${{MySQL.MYSQLHOST}}`, bukan `127.0.0.1` |
| Migrasi gagal saat start | variabel DB belum lengkap | cek 5 variabel `DB_*` |
| Healthcheck timeout | build/migrasi lambat pada boot pertama | `healthcheckTimeout` sudah 180 detik |
| Storage not writable | permission container | `chmod -R 775 storage bootstrap/cache` di start command |

## 10. Bukti untuk PDF dan esai

1. Screenshot build log (composer + vite) dan start log.
2. Output `php -v` di container Railway.
3. Daftar environment variables (nilai rahasia disensor).
4. Domain live dan hasil uji login dua role.
5. Catatan masalah yang muncul saat deploy beserta solusinya (untuk bagian troubleshooting esai).

## 11. Catatan jujur untuk esai

`php artisan serve` adalah server bawaan PHP: satu proses, cukup untuk demo UAS tetapi bukan konfigurasi produksi ideal. Untuk trafik nyata, ganti start command ke `php-fpm` + `nginx` (atau FrankenPHP), dan jalankan `php artisan migrate` sebagai pre-deploy command, bukan di dalam start command.
