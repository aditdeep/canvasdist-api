<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberCardController extends Controller
{
    /**
     * Ambil kartu member user (dibuat otomatis kalau belum ada).
     * card_number & qr_code dipakai frontend untuk render desain kartu ATM-style
     * (nama, level, saldo diambil terpisah dari endpoint /wallet).
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $card = MemberCard::firstOrCreate(
            ['user_id' => $user->id],
            [
                'card_number' => $this->generateCardNumber($user->id),
                'qr_code' => 'CDMEMBER-' . $user->id . '-' . Str::random(10),
                'level' => $user->role,
                'issued_at' => now(),
            ]
        );

        return response()->json($card->load('user.wallet'));
    }

    protected function generateCardNumber(int $userId): string
    {
        // Format ala kartu ATM: 4 blok 4 digit
        $padded = str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
        return sprintf('%s %s %s %s', '2026', '0819', $padded, substr(strtoupper(Str::random(4)), 0, 4));
    }
}
