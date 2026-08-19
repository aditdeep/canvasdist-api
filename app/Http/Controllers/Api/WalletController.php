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
     * List metode pembayaran aktif dari Duitku untuk ditampilkan sebagai pilihan
     * channel (VA/QRIS/e-wallet) sebelum user klik top up.
     */
    public function paymentMethods(Request $request)
    {
        $amount = (float) $request->query('amount', 50000);

        return response()->json($this->duitku->getPaymentMethods($amount));
    }

    public function topup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'nullable|string|max:2',
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

        // Default BC (BCA Virtual Account) kalau user belum pilih metode — Duitku
        // mewajibkan paymentMethod diisi kode channel valid, tidak bisa dikosongkan.
        $duitkuResponse = $this->duitku->createTransaction(
            reference: $reference,
            amount: (float) $request->amount,
            customerName: $request->user()->name,
            customerEmail: $request->user()->email,
            paymentMethod: $request->payment_method ?: 'BC',
        );

        $transaction->update(['payload' => $duitkuResponse]);

        return response()->json([
            'transaction' => $transaction,
            'payment_url' => $duitkuResponse['paymentUrl'] ?? null,
            'va_number' => $duitkuResponse['vaNumber'] ?? null,
        ]);
    }
}
