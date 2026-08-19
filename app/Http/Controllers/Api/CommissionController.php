<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    public function index(Request $request)
    {
        $query = Commission::with('sourceOrder');

        // User biasa cuma lihat komisi miliknya sendiri; admin bisa lihat semua
        if (!$request->user()->isRole('super_admin')) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Cairkan komisi ke saldo user. Bisa dipanggil manual oleh admin,
     * atau dijadwalkan otomatis (mis. tiap akhir bulan) via scheduled command.
     */
    public function payout(Commission $commission)
    {
        if ($commission->status === 'paid') {
            return response()->json(['message' => 'Komisi ini sudah dicairkan'], 422);
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $commission->user_id], ['balance' => 0]);

        $this->walletService->credit(
            $wallet,
            (float) $commission->amount,
            'commission',
            'COMMISSION-' . $commission->id,
            "Komisi {$commission->level} dari order #{$commission->source_order_id}"
        );

        $commission->update(['status' => 'paid', 'paid_at' => now()]);

        return response()->json($commission);
    }
}
