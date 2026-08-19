# Deploy `canvasdist-api` ke VPS

Panduan ini untuk deploy backend Laravel ke VPS kamu, di folder project **baru**
(terpisah dari `mt5split`/`mikala-project` yang sudah ada).

Asumsi: Ubuntu VPS, Nginx, PHP-FPM, PostgreSQL. Kalau stack VPS kamu beda
(misal pakai Apache atau MySQL), kasih tau — panduan ini tinggal disesuaikan.

---

## 0. Cek dulu apa yang sudah terpasang

SSH ke VPS, lalu cek versi yang sudah ada (kemungkinan besar sudah terpasang
karena project Laravel lain sudah jalan di sana):

```bash
php -v          # butuh PHP 8.2+
composer -V
psql --version
nginx -v
```

Kalau salah satu belum ada, install dulu:

```bash
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath unzip git nginx postgresql postgresql-contrib

# Composer (kalau belum ada)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 1. Buat folder project baru

```bash
sudo mkdir -p /var/www/canvasdist-api
sudo chown -R $USER:$USER /var/www/canvasdist-api
cd /var/www/canvasdist-api
```

## 2. Clone repo dari GitHub

```bash
git clone https://github.com/aditdeep/canvasdist-api.git .
```

> Repo saat ini **public**, jadi clone langsung tanpa token. Kalau nanti kamu ubah
> jadi private, pakai: `git clone https://<token>@github.com/aditdeep/canvasdist-api.git .`

## 3. Install dependency Laravel

Repo yang aku push berisi `app/`, `database/`, `routes/`, `config/` — tapi **belum**
project Laravel penuh (tidak ada `composer.json`, `vendor/`, `artisan`, dll, karena
sandbox pembuatannya tidak bisa akses Packagist). Jadi generate dulu base project-nya,
lalu masukkan folder tadi:

```bash
cd /var/www
composer create-project laravel/laravel canvasdist-fresh "^11.0"

# Copy folder hasil clone repo ke atas project fresh
cp -r canvasdist-api/app/Models/* canvasdist-fresh/app/Models/
cp -r canvasdist-api/app/Http/Controllers/* canvasdist-fresh/app/Http/Controllers/
cp -r canvasdist-api/app/Services canvasdist-fresh/app/
cp -r canvasdist-api/database/migrations/* canvasdist-fresh/database/migrations/
cp canvasdist-api/database/seeders/DatabaseSeeder.php canvasdist-fresh/database/seeders/
cp canvasdist-api/routes/api.php canvasdist-fresh/routes/api.php
cp canvasdist-api/config/duitku.php canvasdist-fresh/config/

# Ganti folder lama dengan yang sudah lengkap
rm -rf canvasdist-api
mv canvasdist-fresh canvasdist-api
cd canvasdist-api
```

Install Sanctum (dipakai untuk auth token API):

```bash
composer require laravel/sanctum
php artisan install:api
```

> `php artisan install:api` akan generate migration Sanctum & daftarkan middleware —
> ikuti saja prompt default-nya. Kalau muncul `ERROR API routes file already exists`,
> itu wajar (karena `routes/api.php` sudah kita isi duluan) — lanjut saja.

### ⚠️ Wajib: perbaiki `bootstrap/app.php`

Laravel 11 fresh install **tidak otomatis memuat `routes/api.php`**, meskipun Sanctum
sudah terpasang. Tanpa langkah ini, semua endpoint `/api/*` akan selalu 404.

```bash
nano bootstrap/app.php
```

Ubah bagian `->withRouting(...)` jadi:

```php
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

### ⚠️ Wajib: hapus migration `users` bawaan Laravel

Laravel 11 fresh install sudah punya migration `0001_01_01_000000_create_users_table.php`
sendiri yang juga membuat tabel `users`. Migration kita (`2026081900_create_users_table.php`)
punya skema berbeda (role, parent_id, dst) — kalau keduanya jalan akan konflik
`relation "users" already exists`.

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
```

File itu juga membuat tabel `sessions` — kalau nanti butuh, set di `.env`:

```env
SESSION_DRIVER=file
```

## 4. Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```bash
nano .env
```

Isi bagian ini:

```env
APP_NAME=CanvasDist
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.canvasdist.namadomainkamu.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=canvasdist
DB_USERNAME=canvasdist_user
DB_PASSWORD=isi_password_kuat_disini

DUITKU_MERCHANT_CODE=isi_dari_dashboard_duitku
DUITKU_API_KEY=isi_dari_dashboard_duitku
DUITKU_ENV=sandbox
DUITKU_CALLBACK_URL=https://api.canvasdist.namadomainkamu.com/api/payment/duitku/callback
DUITKU_RETURN_URL=https://app.canvasdist.namadomainkamu.com/saldo

WHATSAPP_PROVIDER=fonnte
WHATSAPP_API_KEY=isi_api_key_fonnte
```

## 5. Buat database PostgreSQL

```bash
sudo -u postgres psql
```

Di dalam prompt psql:

```sql
CREATE DATABASE canvasdist;
CREATE USER canvasdist_user WITH ENCRYPTED PASSWORD 'password_yang_sama_di_env';
GRANT ALL PRIVILEGES ON DATABASE canvasdist TO canvasdist_user;
\q
```

## 6. Migrate + seed

```bash
php artisan migrate --seed
```

> Kalau sempat migrate sebagian lalu gagal (misal karena lupa hapus migration `users`
> bawaan di atas), reset bersih dengan `php artisan migrate:fresh --seed` — aman karena
> database masih baru/kosong.

Kalau sukses, akan ada data uji: admin, wilayah, agen, reseller, sales
(lihat `database/seeders/DatabaseSeeder.php` untuk kredensialnya, semua password `password`).

**Ganti password akun seed ini sebelum benar-benar dipakai production**, contoh via tinker:

```bash
php artisan tinker
```
```php
$user = User::where('email', 'admin@canvasdist.test')->first();
$user->password = bcrypt('password_baru_kamu');
$user->save();
exit
```

## 7. Set permission

```bash
sudo chown -R www-data:www-data /var/www/canvasdist-api
sudo chmod -R 755 storage bootstrap/cache
```

### Wajib: link storage publik (untuk foto checkin/POD/buyback)

```bash
php artisan storage:link
```

Tanpa ini, foto yang diupload (checkin canvasing, POD pengiriman, buyback barang bekas)
akan tersimpan tapi **tidak bisa diakses lewat URL publik** — pastikan juga Nginx
mengizinkan akses ke folder `/storage` (sudah otomatis lewat symlink `public/storage`).

### Wajib: naikkan limit upload file (Nginx + PHP)

Default Nginx (`client_max_body_size`) dan PHP (`upload_max_filesize`) sering
terlalu kecil (1-2MB) untuk foto dari kamera HP, menyebabkan upload checkin/POD/
buyback gagal dengan error yang membingungkan (biasanya terlihat sebagai error
jaringan generik, bukan pesan validasi yang jelas).

Edit config Nginx untuk site ini (`/etc/nginx/sites-available/canvasdist-api`),
tambahkan di dalam blok `server {}`:

```nginx
client_max_body_size 10M;
```

Edit PHP-FPM config (`/etc/php/8.3/fpm/php.ini`):

```ini
upload_max_filesize = 10M
post_max_size = 10M
```

Reload keduanya:

```bash
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
```

## 8. Konfigurasi Nginx

Buat file config baru (folder baru, tidak menimpa config `mt5split`):

```bash
sudo nano /etc/nginx/sites-available/canvasdist-api
```

Isi:

```nginx
server {
    listen 80;
    server_name api.canvasdist.namadomainkamu.com;
    root /var/www/canvasdist-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/canvasdist-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 9. Arahkan domain/subdomain

Di DNS provider domain kamu, tambahkan A record:

```
api.canvasdist.namadomainkamu.com  →  IP VPS kamu
```

## 10. SSL gratis via Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.canvasdist.namadomainkamu.com
```

Pilih opsi redirect HTTP → HTTPS saat ditanya.

## 11. Set CORS supaya frontend Vercel bisa akses

```bash
nano config/cors.php
```

Pastikan `allowed_origins` include domain Vercel kamu nanti:

```php
'allowed_origins' => [
    'https://canvasdist-web.vercel.app',       // ganti sesuai domain Vercel final
    'https://app.canvasdist.namadomainkamu.com', // kalau pakai custom domain
],
```

## 12. Test

```bash
curl https://api.canvasdist.namadomainkamu.com/api/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@canvasdist.test","password":"password"}'
```

Kalau balikin JSON berisi `token`, backend sudah live. 🎉

---

## Checklist sebelum benar-benar production

- [ ] Ganti semua password akun seed
- [ ] `APP_DEBUG=false` (jangan expose stack trace ke publik)
- [ ] Duitku: pakai kredensial production (bukan sandbox) setelah ditest
- [ ] Setup backup database rutin (`pg_dump` terjadwal via cron)
- [ ] Setup supervisor kalau nanti pakai queue (`php artisan queue:work`) — belum dibutuhkan untuk versi sekarang
- [ ] Set `SESSION_DOMAIN` / `SANCTUM_STATEFUL_DOMAINS` di `.env` kalau nanti butuh cookie-based auth (saat ini API pakai token Bearer biasa, jadi tidak wajib)

---

## Update berikutnya (setelah ada perubahan kode)

Setiap kali ada update dari GitHub:

```bash
cd /var/www/canvasdist-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
sudo systemctl reload php8.3-fpm
```
