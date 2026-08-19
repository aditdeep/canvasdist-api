<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrasi WhatsApp Business notification. Default pakai Fonnte
 * (konsisten dengan yang sudah pernah dipakai di project lain).
 * Ganti implementasi di sini kalau mau pindah ke provider lain (Qontak, dll) —
 * controller pemanggilnya tidak perlu berubah.
 */
class WhatsappService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key', env('WHATSAPP_API_KEY'));
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->apiKey) {
            Log::warning('WhatsApp API key belum dikonfigurasi, notifikasi dilewati.');
            return false;
        }

        $response = Http::withHeaders(['Authorization' => $this->apiKey])
            ->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
            ]);

        return $response->successful();
    }

    // Template notifikasi siap pakai untuk event-event utama sistem
    public function notifyOrderReceived(string $phone, string $orderNo): bool
    {
        return $this->send($phone, "Order {$orderNo} kamu sudah kami terima dan sedang diproses. Terima kasih!");
    }

    public function notifyOrderShipped(string $phone, string $doNumber): bool
    {
        return $this->send($phone, "Barang kamu sudah dikirim. No. Surat Jalan: {$doNumber}. Cek posisi kurir di app ya.");
    }

    public function notifyWalletTopup(string $phone, float $amount): bool
    {
        $formatted = number_format($amount, 0, ',', '.');
        return $this->send($phone, "Top up saldo sebesar Rp{$formatted} berhasil masuk ke akun kamu.");
    }

    public function notifyCommissionPaid(string $phone, float $amount): bool
    {
        $formatted = number_format($amount, 0, ',', '.');
        return $this->send($phone, "Komisi sebesar Rp{$formatted} sudah cair ke saldo kamu. Cek di menu Saldo.");
    }
}
