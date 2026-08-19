<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    public function __construct(protected WalletService $walletService, protected NotificationService $notifications) {}

    public function index(Request $request)
    {
        $query = Withdrawal::with('user')->latest();

        if (!$request->user()->isRole('super_admin', 'wilayah', 'agen')) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Ajukan penarikan saldo. Saldo langsung dikunci (dikurangi) saat pengajuan
     * dibuat untuk mencegah double-withdraw, dan dikembalikan otomatis kalau
     * pengajuan ditolak.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50000',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_holder_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id], ['balance' => 0]);

        if ((float) $wallet->balance < $request->amount) {
            return response()->json(['message' => 'Saldo tidak mencukupi'], 422);
        }

        $withdrawal = Withdrawal::create([
            ...$validator->validated(),
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $this->walletService->debit(
            $wallet,
            (float) $request->amount,
            'payment',
            'WITHDRAWAL-' . $withdrawal->id,
            'Pengajuan penarikan saldo (menunggu persetujuan)'
        );

        return response()->json($withdrawal, 201);
    }

    public function show(Withdrawal $withdrawal)
    {
        return response()->json($withdrawal->load('user', 'processedBy'));
    }

    /**
     * Approve/reject oleh admin/agen/wilayah. Kalau ditolak, saldo yang sudah
     * dikunci saat pengajuan dikembalikan otomatis.
     */
    public function update(Request $request, Withdrawal $withdrawal)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|required_if:status,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan ini sudah diproses'], 422);
        }

        if ($request->status === 'rejected') {
            $wallet = Wallet::firstOrCreate(['user_id' => $withdrawal->user_id], ['balance' => 0]);
            $this->walletService->credit(
                $wallet,
                (float) $withdrawal->amount,
                'refund',
                'WITHDRAWAL-' . $withdrawal->id,
                'Pengajuan penarikan ditolak, saldo dikembalikan'
            );
        }

        $withdrawal->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        $this->notifications->notifyWithdrawalProcessed($withdrawal->user, $request->status, (float) $withdrawal->amount);

        return response()->json($withdrawal->fresh()->load('user'));
    }
}
