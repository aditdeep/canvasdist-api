<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Integrasi Duitku (payment gateway).
 *
 * PENTING: signature & endpoint di bawah ini mengikuti skema umum Duitku API v2
 * (Invoice/Pop). Sebelum production, cocokkan lagi field & endpoint persis dengan
 * dokumentasi resmi Duitku terbaru di https://docs.duitku.com — terutama karena
 * merchant code & format bisa beda tergantung produk Duitku yang dipakai
 * (Invoice, Pop, atau Disbursement).
 */
class DuitkuService
{
    protected string $merchantCode;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = config('duitku.merchant_code');
        $this->apiKey = config('duitku.api_key');
        $this->baseUrl = config('duitku.env') === 'production'
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';
    }

    /**
     * Buat transaksi baru (top-up saldo / pembayaran order).
     * $reference = merchantOrderId di sisi Duitku, harus unik.
     */
    public function createTransaction(string $reference, float $amount, string $customerName, string $customerEmail): array
    {
        $paymentAmount = (int) round($amount);
        $signature = md5($this->merchantCode . $reference . $paymentAmount . $this->apiKey);

        $response = Http::post("{$this->baseUrl}/createInvoice", [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $paymentAmount,
            'merchantOrderId' => $reference,
            'productDetails' => 'CanvasDist - Top Up Saldo / Pembayaran',
            'email' => $customerEmail,
            'customerVaName' => $customerName,
            'callbackUrl' => config('duitku.callback_url'),
            'returnUrl' => config('duitku.return_url'),
            'signature' => $signature,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Verifikasi signature callback dari Duitku sebelum memproses status pembayaran.
     * Signature callback Duitku: md5(merchantCode + amount + merchantOrderId + apiKey)
     */
    public function verifyCallbackSignature(array $payload): bool
    {
        $expected = md5(
            $this->merchantCode
            . ($payload['amount'] ?? '')
            . ($payload['merchantOrderId'] ?? '')
            . $this->apiKey
        );

        return hash_equals($expected, $payload['signature'] ?? '');
    }
}
