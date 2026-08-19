<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Services\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(protected DuitkuService $duitku) {}

    public function show(Request $request)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id], ['balance' => 0]);

        return response()->json($wallet);
    }

    public function mutations(Request $request)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id], ['balance' => 0]);

        return response()->json($wallet->mutations()->latest()->paginate(20));
    }

    /**
     * Mulai top-up saldo lewat Duitku. Balik response berisi payment URL untuk dibuka
     * user (redirect ke halaman pembayaran Duitku). Saldo baru bertambah setelah
     * callback Duitku diterima & sukses — lihat DuitkuController@callback.
     */
    public function topup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id], ['balance' => 0]);

        $reference = 'TOPUP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));

        $transaction = PaymentTransaction::create([
            'reference' => $reference,
            'gateway' => 'duitku',
            'wallet_id' => $wallet->id,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        $duitkuResponse = $this->duitku->createTransaction(
            reference: $reference,
            amount: (float) $request->amount,
            customerName: $request->user()->name,
            customerEmail: $request->user()->email,
        );

        $transaction->update(['payload' => $duitkuResponse]);

        return response()->json([
            'transaction' => $transaction,
            'payment_url' => $duitkuResponse['paymentUrl'] ?? null,
        ]);
    }
}
