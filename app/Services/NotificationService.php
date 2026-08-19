<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kirim push notification ke device mobile via Expo Push API.
 * Dokumentasi: https://docs.expo.dev/push-notifications/sending-notifications/
 */
class NotificationService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (!$user->push_token) {
            return;
        }

        try {
            Http::post('https://exp.host/--/api/v2/push/send', [
                'to' => $user->push_token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim push notification: ' . $e->getMessage());
        }
    }

    public function notifyDeliveryAssigned(User $courier, string $doNumber, ?int $legSequence = null): void
    {
        $body = $legSequence
            ? "Kamu ditugaskan untuk etape {$legSequence} pengiriman {$doNumber}."
            : "Kamu ditugaskan untuk pengiriman {$doNumber}.";

        $this->sendToUser($courier, 'Pengiriman Baru', $body, ['type' => 'delivery_assigned', 'do_number' => $doNumber]);
    }

    public function notifyWithdrawalProcessed(User $user, string $status, float $amount): void
    {
        $label = $status === 'approved' ? 'disetujui' : 'ditolak';
        $this->sendToUser(
            $user,
            'Penarikan Saldo ' . ucfirst($label),
            "Pengajuan penarikan saldo sebesar Rp" . number_format($amount, 0, ',', '.') . " telah {$label}.",
            ['type' => 'withdrawal_processed']
        );
    }
}
