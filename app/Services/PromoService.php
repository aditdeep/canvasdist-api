<?php

namespace App\Services;

use App\Models\Promo;

class PromoService
{
    /**
     * Hitung diskon untuk satu item order.
     * Aturan sederhana: cari promo aktif yang cocok dengan target_level & min_qty,
     * ambil yang nilainya paling menguntungkan customer (bisa dikembangkan lagi).
     *
     * discount_fixed & tiered diperlakukan sebagai potongan PER UNIT (dikali qty),
     * konsisten dengan cara harga diskon ditampilkan ke customer di storefront
     * (lihat bestPromoForDisplay()) — sebelumnya dua logika ini beda cara hitung,
     * menyebabkan harga yang ditampilkan vs yang ditagih saat checkout beda.
     */
    public function calculateItemDiscount(int $qty, float $price, ?string $targetLevel = null): float
    {
        $promo = $this->bestPromoForDisplay($targetLevel, $qty);

        if (!$promo) {
            return 0;
        }

        $discountPerUnit = $this->discountPerUnit($promo, $price);

        return round($discountPerUnit * $qty, 2);
    }

    /**
     * Cari 1 promo terbaik (berdasarkan nilai diskon aktual per unit, bukan
     * cuma angka "value" mentah) untuk ditampilkan di storefront (grid/detail
     * produk) MAUPUN dipakai saat checkout — method yang sama dipakai di
     * kedua tempat supaya harga yang ditampilkan = harga yang ditagih.
     */
    public function bestPromoForDisplay(?string $targetLevel, int $qty = 1): ?Promo
    {
        $promos = Promo::where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where(function ($q) use ($targetLevel) {
                $q->whereNull('target_level');
                if ($targetLevel) {
                    $q->orWhere('target_level', $targetLevel);
                }
            })
            ->where(function ($q) use ($qty) {
                $q->whereNull('min_qty')->orWhere('min_qty', '<=', $qty);
            })
            ->get();

        $best = null;
        $bestDiscountPercent = -1;

        foreach ($promos as $promo) {
            // Bandingkan berdasarkan persentase potongan supaya adil antara
            // tipe percent vs fixed (nilai mentah "value" tidak bisa dibandingkan langsung).
            $comparablePercent = $promo->type === 'discount_percent' ? (float) $promo->value : null;

            if ($comparablePercent !== null && $comparablePercent > $bestDiscountPercent) {
                $bestDiscountPercent = $comparablePercent;
                $best = $promo;
            } elseif ($best === null) {
                $best = $promo; // fallback: minimal ada 1 promo dipilih walau tipe fixed/tiered
            }
        }

        return $best;
    }

    public function discountPerUnit(Promo $promo, float $unitPrice): float
    {
        return match ($promo->type) {
            'discount_percent' => round($unitPrice * ((float) $promo->value / 100), 2),
            'discount_fixed', 'tiered' => min((float) $promo->value, $unitPrice),
            default => 0,
        };
    }
}
