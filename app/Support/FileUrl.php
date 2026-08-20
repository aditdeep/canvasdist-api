<?php

namespace App\Support;

/**
 * Helper path file publik. Sengaja TIDAK pakai Storage::url() / asset() karena
 * itu bergantung ke config('app.url') — kalau APP_URL di .env salah/lupa diisi
 * (mis. masih default http://localhost:8000), semua foto jadi broken link di
 * production meski file-nya sendiri sukses ke-upload.
 *
 * Sebagai gantinya kita simpan PATH RELATIF saja ("/storage/produk/xxx.jpg"),
 * dan frontend (web/mobile) yang menambahkan domain API di depannya. Jadi
 * benar di environment manapun tanpa perlu APP_URL dikonfigurasi persis.
 */
class FileUrl
{
    public static function relative(string $storagePath): string
    {
        return '/storage/' . ltrim($storagePath, '/');
    }
}
