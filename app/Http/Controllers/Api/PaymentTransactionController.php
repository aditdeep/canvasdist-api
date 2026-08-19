<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    /**
     * Riwayat transaksi payment gateway (top up saldo & pembayaran order) milik user yang login.
     */
    public function index(Request $request)
    {
        $walletId = $request->user()->wallet?->id;

        $query = PaymentTransaction::query()->latest();

        if ($walletId) {
            $query->where(function ($q) use ($walletId, $request) {
                $q->where('wallet_id', $walletId);
                if (!$request->user()->isRole('super_admin')) {
                    return;
                }
            });
        }

        return response()->json($query->paginate(20));
    }
}
