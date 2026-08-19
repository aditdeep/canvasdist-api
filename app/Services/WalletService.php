<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletMutation;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Tambah saldo + catat mutasi. Dipakai untuk topup, commission payout, cashback.
     */
    public function credit(Wallet $wallet, float $amount, string $type, ?string $reference = null, ?string $description = null): WalletMutation
    {
        return DB::transaction(function () use ($wallet, $amount, $type, $reference, $description) {
            $before = (float) $wallet->balance;
            $after = $before + $amount;

            $wallet->update(['balance' => $after]);

            return WalletMutation::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }

    /**
     * Kurangi saldo (mis. pembayaran order pakai saldo). Melempar exception kalau saldo kurang.
     */
    public function debit(Wallet $wallet, float $amount, string $type, ?string $reference = null, ?string $description = null): WalletMutation
    {
        if ((float) $wallet->balance < $amount) {
            throw new \RuntimeException('Saldo tidak mencukupi');
        }

        return DB::transaction(function () use ($wallet, $amount, $type, $reference, $description) {
            $before = (float) $wallet->balance;
            $after = $before - $amount;

            $wallet->update(['balance' => $after]);

            return WalletMutation::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }
}
