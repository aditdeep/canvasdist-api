<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Integrasi Duitku (payment gateway) — mengikuti dokumentasi resmi
 * https://docs.duitku.com/api/en/ (Request Transaction / "Invoice API v2").
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
     *
     * paymentMethod dikosongkan (empty string) supaya customer diarahkan ke
     * halaman pilihan metode pembayaran Duitku (VA/QRIS/e-wallet dll),
     * bukan dipaksa ke satu channel tertentu.
     */
    public function createTransaction(string $reference, float $amount, string $customerName, string $customerEmail): array
    {
        $paymentAmount = (int) round($amount);
        $signature = md5($this->merchantCode . $reference . $paymentAmount . $this->apiKey);

        $response = Http::post("{$this->baseUrl}/v2/inquiry", [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => '',
            'merchantOrderId' => $reference,
            'productDetails' => 'CanvasDist - Top Up Saldo / Pembayaran',
            'email' => $customerEmail,
            'customerVaName' => $customerName,
            'callbackUrl' => config('duitku.callback_url'),
            'returnUrl' => config('duitku.return_url'),
            'expiryPeriod' => 60,
            'signature' => $signature,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Verifikasi signature callback dari Duitku sebelum memproses status pembayaran.
     * Signature callback Duitku: md5(merchantCode + amount + merchantOrderId + apiKey)
     * Catatan: body callback dikirim sebagai x-www-form-urlencoded, bukan JSON —
     * Laravel tetap bisa baca lewat $request->all() seperti biasa.
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
