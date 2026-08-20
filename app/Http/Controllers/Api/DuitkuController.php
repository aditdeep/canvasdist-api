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
     * Mulai transaksi pembayaran untuk sebuah order (bukan top-up saldo).
     * Dipakai storefront customer buat "Bayar Sekarang" saat checkout, atau
     * bayar belakangan dari halaman Pesanan kalau tadinya pilih COD.
     */
    public function createTransaction(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'nullable|string|max:2',
            'return_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $order = \App\Models\Order::findOrFail($request->order_id);

        // Hanya pemilik order (customer-nya sendiri) yang boleh bayar order ini
        if ($order->outlet_id !== $request->user()->outlet_id) {
            return response()->json(['message' => 'Order ini bukan milik kamu'], 403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order ini sudah lunas'], 422);
        }

        $reference = 'ORDPAY-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(5));

        $transaction = PaymentTransaction::create([
            'reference' => $reference,
            'gateway' => 'duitku',
            'order_id' => $order->id,
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        $duitku = app(\App\Services\DuitkuService::class);
        $duitkuResponse = $duitku->createTransaction(
            reference: $reference,
            amount: (float) $order->total,
            customerName: $request->user()->name,
            customerEmail: $request->user()->email,
            paymentMethod: $request->payment_method ?: 'BC',
            returnUrl: $request->return_url,
        );

        $transaction->update(['payload' => $duitkuResponse]);

        return response()->json([
            'transaction' => $transaction,
            'payment_url' => $duitkuResponse['paymentUrl'] ?? null,
        ]);
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

            // Kalau ini pembayaran order, tandai order lunas
            if ($transaction->order_id) {
                $transaction->order()->update(['payment_status' => 'paid']);
            }
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
