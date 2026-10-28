# Langkah Deploy gudangku ke VPS

Dokumen ini adalah catatan deploy yang sebenarnya dijalankan, sekaligus bahan bagian "Deployment Step" pada PDF presentasi dan bagian troubleshooting esai.

- Repository: https://github.com/ctwiguna/gudangku
- Live: https://43.173.41.195.sslip.io
- Host: VPS 2 vCPU / 2 GB / 40 GB, Ubuntu Server 24.04.4 LTS, region Jakarta
- Stack server: nginx 1.24.0 + PHP 8.5.12 FPM + MySQL 8.0.46 + Node 22.23.4 + Composer 2.10.3

Catatan pemilihan platform: Railway semula dipilih, tetapi pada 28 Oktober 2026 Railway masih dalam masa pemulihan pasca-insiden 21 Oktober dan **pendaftaran akun baru ditutup tanpa tanggal**. Deploy dipindahkan ke VPS agar versi PHP dan hak akses database bisa ditentukan sendiri sesuai kebutuhan keamanan.

---

## 1. Hardening dasar

```bash
# swap 2 GB (antisipasi build Vite di RAM 2 GB)
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# firewall: hanya 22, 80, 443
sudo ufw default deny incoming && sudo ufw default allow outgoing
sudo ufw allow 22/tcp && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp
sudo ufw --force enable
```

### Mematikan login password SSH

**Jebakan yang ditemui:** menaruh `PasswordAuthentication no` di `/etc/ssh/sshd_config.d/99-*.conf` **tidak berpengaruh**. Ubuntu memuat `Include /etc/ssh/sshd_config.d/*.conf` di baris atas, dan sshd memakai **kemunculan pertama** sebuah direktif. File `50-cloud-init.conf` berisi `PasswordAuthentication yes`, jadi ia menang atas `99-`.

Perbaikan: pakai awalan angka lebih kecil, dan perbaiki juga file cloud-init.

```bash
printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\nPermitRootLogin prohibit-password\nMaxAuthTries 3\nX11Forwarding no\n' \
  | sudo tee /etc/ssh/sshd_config.d/00-gudangku-hardening.conf
printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\n' \
  | sudo tee /etc/ssh/sshd_config.d/50-cloud-init.conf
sudo sshd -t && sudo systemctl reload ssh
sudo sshd -T | grep -E '^(passwordauthentication|permitrootlogin|pubkeyauthentication)'
```

Hasil: `passwordauthentication no`, `permitrootlogin without-password`, `pubkeyauthentication yes`.

## 1b. Dua jebakan yang muncul saat mengisi data (D1.3)

**Jebakan A — mengubah migrasi yang sudah pernah jalan.** Migrasi `users` semula memakai versi bawaan Laravel dan sudah tercatat di tabel `migrations` pada deploy pertama. Ketika kolom `outlet_id`, `role`, dan `is_active` ditambahkan dengan mengedit file migrasi lama, server tidak menerapkannya karena migrasi itu sudah dianggap selesai. Gejalanya: seeder gagal dengan `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'role' in 'field list'`, padahal database lokal baik-baik saja karena di sana dijalankan `migrate:fresh`.

Perbaikan untuk server demo: `php artisan migrate:fresh --seed --force`. Aturan jangka panjangnya: **jangan pernah mengedit migrasi yang sudah dijalankan**; tambahkan migrasi baru.

Verifikasi skema setelah perbaikan:

```sql
SHOW COLUMNS FROM users;
-- id, outlet_id, name, email, email_verified_at, password, role, is_active, remember_token, created_at, updated_at
```

**Jebakan B — permission `storage/logs`.** Error seeder menutupi dirinya sendiri karena `storage/logs/laravel.log` tidak bisa dibuka: CLI berjalan sebagai `ubuntu`, sedangkan berkas dibuat oleh `www-data` (php-fpm). Laravel gagal menulis log, lalu melempar exception permission yang menyembunyikan error aslinya.

```bash
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
touch storage/logs/laravel.log && chmod 664 storage/logs/laravel.log
```

Pelajaran untuk esai: ketika sebuah aplikasi "gagal tanpa pesan jelas", periksa jalur logging lebih dulu. Hak tulis log adalah bagian dari observability, bukan sekadar kosmetik.

## 2. PHP 8.5.12

Ubuntu 24.04 hanya menyediakan PHP 8.3, sedangkan kebutuhan keamanan proyek adalah PHP 8.5.12 (patch CVE-2026-109618 dan CVE-2026-109619).

**Jebakan yang ditemui:** `sudo add-apt-repository ppa:ondrej/php` gagal dengan `TimeoutError: [Errno 110] Connection timed out`. Penyebabnya bukan repo PPA-nya (host PPA menjawab 200), melainkan `api.launchpad.net` yang dipanggil `add-apt-repository` dan diblokir di jaringan VPS ini.

Perbaikan: tambahkan PPA secara manual, ambil signing key lewat port HKP 11371 yang terbuka.

```bash
KEYID=14AA40EC0831756756D7F66C4F4EA0AAE5267A6C
gpg --no-default-keyring --keyring /tmp/ondrej.gpg \
  --keyserver hkp://keyserver.ubuntu.com:11371 --recv-keys $KEYID
gpg --no-default-keyring --keyring /tmp/ondrej.gpg --export --armor $KEYID \
  | sudo gpg --dearmor -o /usr/share/keyrings/ondrej-php.gpg

echo "deb [signed-by=/usr/share/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
  | sudo tee /etc/apt/sources.list.d/ondrej-php.list
sudo apt-get update
```

**Jebakan kedua:** paket `php8.5-opcache` tidak ada sehingga `apt-get install` berhenti dengan `E: Unable to locate package` (exit 100). Opcache sudah menyatu di paket `php8.5-cli`/`php8.5-fpm`.

```bash
sudo apt-get install -y nginx php8.5-fpm php8.5-cli php8.5-mysql php8.5-mbstring \
  php8.5-xml php8.5-curl php8.5-zip php8.5-intl php8.5-bcmath php8.5-gd
```

## 3. MySQL dan user aplikasi non-root

Sesuai kebutuhan keamanan: aplikasi **tidak** memakai root.

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS gudangku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'gudangku'@'localhost' IDENTIFIED BY '<password-kuat>';"
sudo mysql -e "GRANT ALL PRIVILEGES ON gudangku.* TO 'gudangku'@'localhost'; FLUSH PRIVILEGES;"
```

Verifikasi hak akses (hanya satu database, tanpa hak global):

```
GRANT USAGE ON *.* TO `gudangku`@`localhost`
GRANT ALL PRIVILEGES ON `gudangku`.* TO `gudangku`@`localhost`
```

## 4. Node, Composer, dan aplikasi

Node dari repo Ubuntu masih 18.19 (Vite 8 memerlukan ≥ 20), jadi memakai NodeSource 22.

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs

curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

sudo mkdir -p /var/www && sudo chown ubuntu:ubuntu /var/www
git clone https://github.com/ctwiguna/gudangku.git /var/www/gudangku
cd /var/www/gudangku
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
```

## 5. Environment produksi

```bash
cd /var/www/gudangku
# .env: APP_ENV=production, APP_DEBUG=false, APP_TIMEZONE=Asia/Jakarta,
#       APP_LOCALE=id, SESSION_DRIVER=database, CACHE_STORE=database,
#       QUEUE_CONNECTION=sync, DB_* mengarah ke user gudangku
php artisan key:generate --force
php artisan migrate --force
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 6. nginx + HTTPS

Virtual host: root ke `public/`, PHP lewat socket `unix:/run/php/php8.5-fpm.sock`, blok port 80 me-redirect ke HTTPS, ditambah header keamanan (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).

```bash
sudo ln -sf /etc/nginx/sites-available/gudangku /etc/nginx/sites-enabled/gudangku
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d 43.173.41.195.sslip.io --non-interactive --agree-tos \
  --register-unsafely-without-email --redirect
```

Domain memakai `sslip.io` agar dapat sertifikat Let's Encrypt tanpa membeli domain. Sertifikat berlaku 89 hari dan diperbarui otomatis oleh `certbot.timer`.

Setelah HTTPS aktif, `APP_URL` diubah ke domain HTTPS dan `SESSION_SECURE_COOKIE=true`, lalu `config:cache`, `route:cache`, `view:cache`.

## 7. Hasil verifikasi

| Uji | Hasil |
| :--- | :--- |
| `https://43.173.41.195.sslip.io/` | 200, elemen `data-page` Inertia dan asset build ada |
| `https://43.173.41.195.sslip.io/up` | 200 (healthcheck) |
| `http://43.173.41.195/` | 301 ke domain HTTPS |
| Header keamanan | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` terpasang |
| Login password SSH | ditolak (`Permission denied (publickey)`) |
| Hak akses MySQL | user aplikasi hanya pada database `gudangku` |
| Sertifikat | Let's Encrypt, valid 89 hari, renewal otomatis |

## 8. Catatan jujur untuk esai

1. Ubuntu 24.04 menyediakan **MySQL 8.0.46**, bukan 26.10.0 seperti tertulis di PRD. Tidak ada fitur aplikasi yang bergantung pada versi itu, tetapi dokumen tidak boleh mengklaim sesuai PRD. PHP 8.5.12 justru terpenuhi persis, dan itu bagian yang relevan untuk mitigasi CVE.
2. Domain `sslip.io` dipakai karena keterbatasan waktu; untuk produksi sebenarnya cukup mengganti `server_name`, mengarahkan A record, dan menerbitkan ulang sertifikat.
3. Selama penyiapan, VPS diakses memakai password root sekali untuk memasang kunci SSH publik; setelah itu `PasswordAuthentication` dimatikan sehingga password tersebut tidak lagi berlaku.
4. Konsol VNC dari panel penyedia bermasalah pada pemetaan keyboard (`atkbd serio0: Unknown key pressed`), sehingga penyiapan dilakukan lewat SSH, bukan konsol.
