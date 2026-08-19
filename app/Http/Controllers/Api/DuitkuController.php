<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DuitkuController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * Endpoint generik untuk mulai transaksi pembayaran order (bukan top-up saldo).
     * Untuk top-up saldo pakai WalletController@topup.
     */
    public function createTransaction(Request $request)
    {
        // Alur sama seperti WalletController@topup, hanya beda field order_id vs wallet_id.
        // Implementasi lengkap menyusul sesuai kebutuhan checkout order non-saldo.
        return response()->json(['message' => 'Gunakan /api/wallet/topup untuk saldo, atau lengkapi endpoint ini untuk pembayaran order langsung.'], 501);
    }

    /**
     * Callback dari Duitku setelah pembayaran selesai/gagal.
     * WAJIB verifikasi signature sebelum update status apapun.
     */
    public function callback(Request $request)
    {
        $payload = $request->all();

        $transaction = PaymentTransaction::where('reference', $payload['merchantOrderId'] ?? null)->first();

        if (!$transaction) {
            Log::warning('Duitku callback: transaksi tidak ditemukan', $payload);
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        $duitku = app(\App\Services\DuitkuService::class);

        if (!$duitku->verifyCallbackSignature($payload)) {
            Log::warning('Duitku callback: signature tidak valid', $payload);
            return response()->json(['message' => 'Signature tidak valid'], 403);
        }

        $resultCode = $payload['resultCode'] ?? null; // '00' = sukses di skema umum Duitku

        if ($resultCode === '00') {
            $transaction->update(['status' => 'success', 'payload' => $payload]);

            // Kalau ini top-up saldo, kreditkan ke wallet
            if ($transaction->wallet_id) {
                $this->walletService->credit(
                    $transaction->wallet->fresh(),
                    (float) $transaction->amount,
                    'topup',
                    $transaction->reference,
                    'Top up saldo via Duitku'
                );
            }

            // TODO: kalau ini pembayaran order (order_id ada), update status order/invoice di sini
        } else {
            $transaction->update(['status' => 'failed', 'payload' => $payload]);
        }

        // Duitku mengharapkan response text "OK" (bukan JSON) untuk beberapa produk mereka —
        // cek dokumentasi resmi untuk format response callback yang persis dibutuhkan.
        return response('OK', 200);
    }

    public function returnUrl(Request $request)
    {
        // User diarahkan ke sini setelah selesai bayar di halaman Duitku.
        // Redirect ke halaman frontend yang sesuai (mis. halaman wallet).
        $reference = $request->query('merchantOrderId');

        return response()->json(['message' => 'Pembayaran diproses, cek status saldo/order.', 'reference' => $reference]);
    }
}
