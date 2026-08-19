<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;

class CommissionService
{
    /**
     * Persentase komisi per level. Pindahkan ke config/table settings kalau perlu
     * diubah-ubah tanpa deploy ulang.
     */
    protected array $percentages = [
        'reseller' => 5.0,
        'agen' => 3.0,
        'wilayah' => 1.5,
    ];

    /**
     * Hitung & catat komisi berjenjang untuk sebuah order yang sudah selesai.
     * Naik dari agent langsung (reseller) ke atasannya (agen) ke atasannya lagi (wilayah).
     * Komisi dibuat dengan status 'pending' — cair ke saldo lewat CommissionController@payout.
     */
    public function generateForOrder(Order $order): void
    {
        if (!$order->agent_id) {
            return;
        }

        $current = User::find($order->agent_id);
        $levelsToClimb = ['reseller', 'agen', 'wilayah'];

        foreach ($levelsToClimb as $level) {
            if (!$current) {
                break;
            }

            if ($current->role === $level) {
                $this->recordCommission($current, $order, $level);
                $current = $current->parent; // naik ke upline
            } elseif (in_array($current->role, $levelsToClimb)) {
                // role user tidak persis match urutan default, tetap catat sesuai role aktualnya
                $this->recordCommission($current, $order, $current->role);
                $current = $current->parent;
            } else {
                $current = $current->parent;
            }
        }
    }

    protected function recordCommission(User $user, Order $order, string $level): void
    {
        $percentage = $this->percentages[$level] ?? 0;

        if ($percentage <= 0) {
            return;
        }

        $amount = round((float) $order->total * ($percentage / 100), 2);

        Commission::create([
            'user_id' => $user->id,
            'source_order_id' => $order->id,
            'level' => $level,
            'percentage' => $percentage,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }
}
