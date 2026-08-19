<?php

namespace App\Services;

use App\Models\Promo;

class PromoService
{
    /**
     * Hitung diskon untuk satu item order.
     * Aturan sederhana: cari promo aktif yang cocok dengan target_level & min_qty,
     * ambil yang nilainya paling menguntungkan customer (bisa dikembangkan lagi).
     */
    public function calculateItemDiscount(int $qty, float $price, ?string $targetLevel = null): float
    {
        $subtotal = $qty * $price;

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

        $bestDiscount = 0;

        foreach ($promos as $promo) {
            $discount = match ($promo->type) {
                'discount_percent' => $subtotal * ((float) $promo->value / 100),
                'discount_fixed', 'tiered' => (float) $promo->value,
                default => 0,
            };

            $bestDiscount = max($bestDiscount, $discount);
        }

        return round($bestDiscount, 2);
    }
}
