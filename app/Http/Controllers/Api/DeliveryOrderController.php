<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Http\Controllers\Api\OrderController;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeliveryOrderController extends Controller
{
    public function __construct(
        protected OrderController $orderController,
        protected NotificationService $notifications,
        protected WalletService $walletService
    ) {}

    public function index(Request $request)
    {
        $query = DeliveryOrder::with('order.outlet', 'courier', 'legs.fromHub', 'legs.toHub', 'legs.courier');

        if ($request->user()->isRole('kurir')) {
            // Kurir lihat DO yang sedang jadi kurir aktifnya, ATAU DO yang punya
            // etape (leg) ditugaskan ke dia meski belum jadi kurir aktif saat ini
            // (misal masih menunggu etape sebelumnya selesai).
            $userId = $request->user()->id;
            $query->where(function ($q) use ($userId) {
                $q->where('courier_id', $userId)
                    ->orWhereHas('legs', fn ($l) => $l->where('courier_id', $userId)->whereIn('status', ['pending', 'in_transit']));
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Terbitkan Surat Jalan (DO) untuk order yang sudah approved, sekaligus assign kurir.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'courier_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($order->status !== 'approved') {
            return response()->json(['message' => 'Order harus berstatus approved sebelum dibuat Surat Jalan'], 422);
        }

        $do = DeliveryOrder::create([
            'do_number' => 'DO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'order_id' => $order->id,
            'courier_id' => $request->courier_id,
            'status' => 'siap_kirim',
        ]);

        $order->update(['status' => 'processing']);

        if ($do->courier_id) {
            $courier = User::find($do->courier_id);
            if ($courier) {
                $this->notifications->notifyDeliveryAssigned($courier, $do->do_number);
            }
        }

        return response()->json($do->load('order'), 201);
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        return response()->json($deliveryOrder->load('order.outlet', 'courier', 'trackings'));
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:siap_kirim,dikirim,sampai_tujuan,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $deliveryOrder->update(['status' => $request->status]);

        if ($request->status === 'dikirim' && !$deliveryOrder->shipped_at) {
            $deliveryOrder->update(['shipped_at' => now(), 'order_id' => $deliveryOrder->order_id]);
            $deliveryOrder->order->update(['status' => 'shipped']);
        }

        return response()->json($deliveryOrder);
    }

    /**
     * Upload bukti terima (foto + tanda tangan digital) -> tandai selesai
     * -> order jadi 'completed' -> trigger komisi jaringan.
     *
     * Terima file upload beneran (multipart/form-data) untuk foto & tanda tangan,
     * disimpan ke storage/app/public/pod dan diakses lewat /storage/pod/....
     */
    public function uploadPod(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:5120', // max 5MB
            'signature' => 'nullable|image|max:2048',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $photoPath = $request->file('photo')->store('pod', 'public');
        $signaturePath = $request->hasFile('signature')
            ? $request->file('signature')->store('pod-signatures', 'public')
            : null;

        $deliveryOrder->update([
            'pod_photo_path' => \App\Support\FileUrl::relative($photoPath),
            'pod_signature_path' => $signaturePath ? \App\Support\FileUrl::relative($signaturePath) : null,
            'status' => 'selesai',
            'delivered_at' => now(),
        ]);

        // Catat titik lokasi terakhir saat POD diambil, kalau kurir kirim koordinat
        if ($request->filled('lat') && $request->filled('lng')) {
            $deliveryOrder->trackings()->create([
                'lat' => $request->lat,
                'lng' => $request->lng,
                'status' => 'selesai',
                'recorded_at' => now(),
            ]);
        }

        $this->orderController->markCompleted($deliveryOrder->order);

        // Bayar kurir — sengaja dijaga try/catch, sama seperti komisi
        // jaringan, supaya gagal bayar kurir tidak menggagalkan penyelesaian
        // pengiriman itu sendiri.
        try {
            $this->payCourier($deliveryOrder);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal bayar kurir untuk DO ' . $deliveryOrder->do_number . ': ' . $e->getMessage());
        }

        return response()->json($deliveryOrder->fresh()->load('order'));
    }

    /**
     * Bayar kurir setelah pengiriman selesai — dua komponen, bisa dipakai
     * salah satu atau digabung sekaligus sesuai kebijakan agen:
     * 1. Nominal tetap per pengiriman (courier_fee_flat milik agen)
     * 2. Persentase dari ongkir order tersebut (courier_fee_percent milik agen)
     */
    protected function payCourier(DeliveryOrder $deliveryOrder): void
    {
        if (!$deliveryOrder->courier_id) {
            return;
        }

        $order = $deliveryOrder->order;
        $agent = $order->agent_id ? User::find($order->agent_id) : null;

        if (!$agent) {
            return;
        }

        $flatFee = (float) $agent->courier_fee_flat;
        $percentFee = (float) $agent->courier_fee_percent > 0
            ? round((float) $order->shipping_fee * ((float) $agent->courier_fee_percent / 100), 2)
            : 0;

        $totalFee = $flatFee + $percentFee;

        if ($totalFee <= 0) {
            return;
        }

        $courier = User::find($deliveryOrder->courier_id);
        if (!$courier) {
            return;
        }

        $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $courier->id], ['balance' => 0]);

        $this->walletService->credit(
            $wallet,
            $totalFee,
            'delivery_fee',
            $deliveryOrder->do_number,
            "Ongkos kirim {$deliveryOrder->do_number}" .
                ($flatFee > 0 && $percentFee > 0 ? " (tetap Rp" . number_format($flatFee, 0, ',', '.') . " + {$agent->courier_fee_percent}% ongkir)" : '')
        );
    }
}
