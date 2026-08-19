<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuybackController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    public function index(Request $request)
    {
        return response()->json(Buyback::with('outlet')->latest()->paginate(20));
    }

    /**
     * Sales/kurir input barang bekas (jerigen dll) yang diterima dari outlet, dengan foto sebagai bukti.
     * Cashback belum masuk saldo sampai diverifikasi (lihat verify()).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id' => 'nullable|exists:visits,id',
            'outlet_id' => 'required|exists:outlets,id',
            'item_type' => 'required|string',
            'qty' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'photo_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $buyback = Buyback::create([
            'visit_id' => $request->visit_id,
            'outlet_id' => $request->outlet_id,
            'item_type' => $request->item_type,
            'qty' => $request->qty,
            'unit_price' => $request->unit_price,
            'cashback_amount' => $request->qty * $request->unit_price,
            'photo_path' => $request->photo_path,
            'status' => 'pending',
        ]);

        return response()->json($buyback, 201);
    }

    public function show(Buyback $buyback)
    {
        return response()->json($buyback->load('outlet', 'visit'));
    }

    /**
     * Verifikasi oleh gudang/admin -> cashback otomatis masuk ke saldo outlet.
     * Catatan: outlet di sini diasumsikan juga terdaftar sebagai User (role reseller/outlet)
     * agar punya wallet. Kalau outlet belum tentu user, ganti target cashback ke agent_id-nya.
     */
    public function update(Request $request, Buyback $buyback)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,verified,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $wasVerified = $buyback->status === 'verified';
        $buyback->update(['status' => $request->status]);

        if ($request->status === 'verified' && !$wasVerified) {
            $agentId = $buyback->outlet->agent_id ?? null;

            if ($agentId) {
                $wallet = Wallet::firstOrCreate(['user_id' => $agentId], ['balance' => 0]);
                $this->walletService->credit(
                    $wallet,
                    (float) $buyback->cashback_amount,
                    'cashback',
                    'BUYBACK-' . $buyback->id,
                    "Cashback {$buyback->item_type} x{$buyback->qty}"
                );
            }
        }

        return response()->json($buyback);
    }

    public function destroy(Buyback $buyback)
    {
        $buyback->delete();

        return response()->json(['message' => 'Data buyback dihapus']);
    }
}
