<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeliveryOrderController extends Controller
{
    public function __construct(protected OrderController $orderController) {}

    public function index(Request $request)
    {
        $query = DeliveryOrder::with('order.outlet');

        if ($request->user()->isRole('kurir')) {
            $query->where('courier_id', $request->user()->id);
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
     */
    public function uploadPod(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validator = Validator::make($request->all(), [
            'pod_photo_path' => 'required|string',
            'pod_signature_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $deliveryOrder->update([
            'pod_photo_path' => $request->pod_photo_path,
            'pod_signature_path' => $request->pod_signature_path,
            'status' => 'selesai',
            'delivered_at' => now(),
        ]);

        $this->orderController->markCompleted($deliveryOrder->order);

        return response()->json($deliveryOrder->fresh()->load('order'));
    }
}
