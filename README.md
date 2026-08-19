# CanvasDist API

Backend (Laravel) untuk aplikasi Canvasing & Distribusi Produk.

> Catatan: struktur ini disiapkan sebagai skeleton. Karena sandbox pembuatan
> repo ini tidak punya akses ke Packagist, jalankan langkah berikut di
> lokal/VPS kamu untuk generate project Laravel penuh, lalu merge folder
> `app/`, `database/`, `routes/` di bawah ke project barunya:
>
> ```bash
> composer create-project laravel/laravel canvasdist-api-fresh "^11.0"
> ```

## Modul & Endpoint (rencana)

| Modul | Endpoint prefix | Deskripsi |
|---|---|---|
| Auth | `/api/auth` | login, register, refresh token (role: admin/agen/sales/gudang/kurir) |
| Master Data | `/api/products`, `/api/outlets`, `/api/regions` | CRUD produk, outlet, wilayah |
| Canvasing | `/api/visits` | cek-in kunjungan, survey outlet |
| Order | `/api/orders` | buat order dari canvasing, approval |
| Inventory | `/api/stocks`, `/api/stock-mutations` | stok per gudang/agen |
| Pengiriman | `/api/delivery-orders`, `/api/tracking` | Surat Jalan, status kirim, POD |
| Piutang | `/api/invoices`, `/api/receivables` | invoice & tagihan |
| Retur | `/api/returns` | proses retur barang |
| Promo | `/api/promos`, `/api/discounts` | promo & diskon berjenjang |
| Komisi | `/api/commissions` | komisi jaringan (wilayah/agen/reseller) |
| Saldo | `/api/wallet`, `/api/wallet/topup` | deposit saldo & mutasi |
| Cashback Bekas | `/api/buyback` | cashback jerigen/barang bekas |
| Payment | `/api/payment/duitku` | integrasi payment gateway Duitku |
| WA Notif | `/api/notifications/whatsapp` | trigger notifikasi WA Business |

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## ⚠️ Setup penting yang mudah terlewat

1. **`bootstrap/app.php`** — Laravel 11 fresh install TIDAK otomatis memuat `routes/api.php`
   meskipun Sanctum sudah di-install. Copy isi `bootstrap/app.php` di repo ini (referensi)
   ke project fresh kamu, atau endpoint `/api/*` akan selalu 404.
2. **Migration `users` bawaan Laravel** — hapus `database/migrations/0001_01_01_000000_create_users_table.php`
   bawaan (skeleton default) sebelum migrate, karena tabel `users` kita sudah didefinisikan
   ulang dengan skema custom (role, parent_id, dst) di migration kita sendiri — akan konflik
   "relation users already exists" kalau keduanya jalan.
3. Set `SESSION_DRIVER=file` di `.env` (bukan `database`) kalau migration `sessions` bawaan ikut terhapus.

Panduan lengkap deploy ke VPS dari nol: lihat [`DEPLOYMENT.md`](./DEPLOYMENT.md).
