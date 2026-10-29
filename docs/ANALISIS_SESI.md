# Analisis Penyimpanan Kredensial Sesi — gudangku

Menjawab pertanyaan: apakah kunci disimpan di localStorage, dan bagaimana best practice expiry (idle, absolute, access vs refresh token) berlaku untuk aplikasi ini.

## 1. Kondisi nyata gudangku

| Pemeriksaan | Hasil |
| :--- | :--- |
| Kemunculan `localStorage` / `sessionStorage` di kode | **0** |
| Penggunaan `Authorization: Bearer` atau token di header | **0** |
| Sanctum / Passport / JWT | **tidak terpasang** |
| Guard auth | hanya `web`, driver **`session`** |
| Cookie sesi | `gudangku-session`: `HttpOnly`, `Secure`, `SameSite=Lax` |
| Cookie CSRF | `XSRF-TOKEN`: `Secure`, `SameSite=Lax`, **tanpa** `HttpOnly` (memang disengaja) |
| Kadaluwarsa | `SESSION_LIFETIME=120` menit, `expire_on_close=false`, `encrypt=false` |

**Kesimpulan:** aplikasi ini **tidak menyimpan kunci apa pun di localStorage**. Tidak ada access token, tidak ada refresh token, tidak ada JWT. Yang ada di browser hanya satu cookie sesi yang tidak bisa dibaca JavaScript, dan satu cookie CSRF yang bukan kredensial.

Jadi kekhawatiran "kunci bisa dilihat lewat Inspect" **tidak berlaku** untuk arsitektur ini. Kekhawatiran itu valid untuk aplikasi SPA yang memakai token API (JWT/OAuth) dan menyimpannya di localStorage atau sessionStorage.

### Catatan penting: `XSRF-TOKEN` memang dibaca JavaScript

Cookie `XSRF-TOKEN` sengaja tidak `HttpOnly`. Itu pola **double-submit CSRF**: frontend membaca nilai cookie dan mengirimkannya kembali di header `X-XSRF-TOKEN`, lalu server membandingkannya dengan token milik sesi.

Cookie itu **bukan kredensial**. Kalau seseorang mencurinya, ia tidak bisa memakainya untuk login atau mengambil alih sesi — nilainya hanya berguna bila dikirim bersama cookie sesi yang asli, dan cookie sesi itu `HttpOnly` sehingga tidak bisa dibaca JavaScript.

### Nuansa yang sering dilewatkan

`HttpOnly` mencegah **pencurian** (exfiltrasi) cookie lewat JavaScript, tetapi **tidak mencegah penyalahgunaan**. Kalau ada XSS, kode penyerang tetap bisa mengirim request memakai cookie yang tidak bisa ia baca (session riding). Karena itu:

1. `HttpOnly` bukan pengganti proteksi XSS.
2. Proteksi CSRF tetap wajib meski cookie `HttpOnly`.
3. Yang benar-benar mengurangi dampak XSS adalah memperpendek masa hidup sesi dan memaksa re-autentikasi untuk aksi sensitif.

## 2. Mekanisme kadaluwarsa: apa yang sudah ada, apa yang belum

Karena sesi disimpan di server (tabel `sessions`), mekanismenya berbeda dari token di client. Kolom yang tersedia:

| Kolom | Fungsi |
| :--- | :--- |
| `id` | ID sesi di cookie; di-`regenerate()` saat login untuk mencegah session fixation |
| `user_id` | pemilik sesi |
| `ip_address`, `user_agent` | konteks perangkat |
| `payload` | isi sesi (saat ini **plaintext**) |
| `last_activity` | timestamp aktivitas terakhir → dasar **idle timeout** |

| Kontrol | Status |
| :--- | :--- |
| Idle timeout (sliding) | **Ada** — `last_activity` + `SESSION_LIFETIME=120` menit, diperpanjang tiap request |
| Session fixation | **Ada** — `regenerate()` saat login, `invalidate()` + `regenerateToken()` saat logout |
| Rate limit login | **Ada** — 5 percobaan per menit |
| Re-auth untuk aksi sensitif | **Sebagian** — hanya ganti password (wajib `current_password`) |
| **Absolute timeout** (batas total sejak login) | **Belum ada** — `sessions` tidak menyimpan `login_at`; idle timeout saja tidak membatasi sesi yang terus aktif |
| `expire_on_close` | **Belum aktif** (false) — sesi bertahan setelah browser ditutup selama cookie ada |
| Enkripsi payload sesi | **Belum aktif** (false) — isi sesi tersimpan plaintext di database |
| Invalidate sesi lain saat ganti password | **Belum ada** |

Poin paling penting: **idle timeout bukan batas atas.** Karena sifatnya sliding, sesi bisa hidup berhari-hari selama pengguna aktif. Industri keuangan biasanya memasang keduanya:

1. **Idle timeout** (mis. 5–15 menit) → melindungi perangkat yang ditinggal terbuka.
2. **Absolute timeout** (mis. 8–12 jam) → memaksa login ulang apa pun yang terjadi.
3. **Re-authentication** untuk transaksi berisiko (transfer, ubah data pembayaran).

## 3. Soal "timestamp di payload"

Saran "di payload dikasih timestamp kapan login" benar arahnya, tapi **timestamp harus berada di server**, bukan di client:

1. Di aplikasi ini payload sesi sudah tersimpan di server (tabel `sessions`), jadi timestamp tidak perlu dikirim ke client.
2. Timestamp yang disimpan di localStorage atau cookie biasa **bisa dimanipulasi pengguna**. Kalau aplikasi memutuskan "sesi masih valid" berdasarkan nilai dari client, penyerang cukup mengubah angka itu.
3. Yang dipakai: waktu dari server. `last_activity` sudah menyediakan itu untuk idle. Untuk absolute, tambahkan cap `login_at`/`created_at` di sisi server lalu bandingkan dengan waktu server saat request masuk.

## 4. Mekanisme 1 vs mekanisme 2: kapan relevan

**Mekanisme 1 (access token saja, logout setelah 5 menit)** dan **mekanisme 2 (access token 5 menit + refresh token 7 hari)** keduanya berbicara tentang arsitektur **stateless/API**: SPA + API terpisah, aplikasi mobile, atau banyak service. Di situ token memang berada di client, sehingga umur token harus pendek dan perlu rotasi.

Aplikasi monolith Laravel + Inertia + session guard **tidak membutuhkan refresh token**, karena:

1. Tidak ada token di client yang bisa dicuri — hanya cookie sesi `HttpOnly`.
2. Perpanjangan sesi terjadi otomatis di server (sliding `last_activity`). Jadi keluhan "user diminta login berkali-kali" sudah teratasi tanpa refresh token.
3. Refresh token menambah permukaan serang baru (rotasi, deteksi reuse, penyimpanan) tanpa manfaat nyata di arsitektur ini.

Padanan konsepnya di dunia session:

| Konsep token | Padanan di sesi server |
| :--- | :--- |
| Access token pendek | `SESSION_LIFETIME` (idle) |
| Absolute cap | kolom `login_at` + middleware |
| Refresh token panjang | kolom `remember_token` (fitur "Ingat saya") |
| Refresh token dicuri → rotasi | revoke sesi lain saat ganti password |

## 5. Rekomendasi untuk gudangku

Konteks nyatanya: aplikasi dipakai di **outlet dengan perangkat bersama** (kasir/tablet), jadi risiko utama bukan pencurian token, melainkan **sesi tertinggal terbuka** di perangkat bersama.

| Prioritas | Kontrol | Nilai sekarang | Usulan | Dampak ke pengguna |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `SESSION_EXPIRE_ON_CLOSE` | `false` | `true` | harus login tiap membuka browser — tepat untuk perangkat bersama |
| 2 | Idle timeout | 120 menit | 30–60 menit | lebih cepat diminta login kalau idle |
| 3 | Enkripsi payload sesi | `false` | `true` | tidak terlihat, payload aman bila DB bocor |
| 4 | Absolute cap sejak login | belum | middleware + kolom `login_at`, mis. 12 jam | login ulang sekali per shift kerja |
| 5 | Revoke sesi lain saat ganti password | belum | invalidasi semua sesi user itu | perangkat lain langsung keluar |
| 6 | Re-auth untuk aksi sensitif | hanya ganti password | tambah untuk hapus massal / ubah harga | sedikit friksi, jauh lebih aman |

Yang **tidak** disarankan untuk aplikasi ini: memasang JWT + refresh token, atau menyimpan kredensial apa pun di localStorage. Keduanya menambah kompleksitas tanpa menutup risiko yang benar-benar ada di sini.

## 6. Urutan risiko yang realistis di aplikasi ini

1. **Sesi tertinggal di perangkat bersama outlet** → mitigasi: idle pendek, expire on close, tombol keluar yang jelas.
2. **CSRF** → sudah: 419 tanpa token, `SameSite=Lax`, XSRF double-submit.
3. **XSS** → sudah: React meng-escape teks, tidak ada `dangerouslySetInnerHTML` (diuji di `SecurityTest`).
4. **Kebocoran field sensitif lewat props** → sudah: dipindai rekursif di 7 route manajer (harga, nominal, total, hutang, nilai stok).
5. **Session fixation** → sudah: `regenerate()` saat login.
6. **Payload sesi plaintext di database** → belum: aktifkan `SESSION_ENCRYPT`.
7. **Pencurian cookie saat transmisi** → sudah: `Secure` + HSTS + redirect HTTPS.

## 7. Ringkasan jawaban

1. Klaim "kunci di localStorage tidak aman" **benar**, dan gudangku tidak melakukannya (0 kemunculan).
2. Best practice nomor 1 (cookie `HttpOnly` + `Secure`) **sudah diterapkan**; tambahan `SameSite=Lax` yang juga penting.
3. Best practice nomor 2 (timestamp) benar arahnya, tetapi timestamp harus di **server**; `last_activity` sudah ada untuk idle, absolute cap belum.
4. Mekanisme 1/2 (access vs refresh token) adalah solusi untuk arsitektur token/API. Untuk aplikasi session-based ini, padanannya adalah kombinasi idle timeout + absolute cap + revoke on password change — tanpa perlu refresh token.
5. Tiga peningkatan yang benar-benar menambah keamanan di konteks outlet: `SESSION_EXPIRE_ON_CLOSE=true`, idle lebih pendek, dan `SESSION_ENCRYPT=true`, plus absolute cap bila ingin setara praktik industri keuangan.
