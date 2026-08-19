<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryTrackingController extends Controller
{
    /**
     * Kurir kirim update posisi GPS secara berkala selama perjalanan.
     */
    public function store(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $tracking = DeliveryTracking::create([
            'delivery_order_id' => $deliveryOrder->id,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'status' => $request->status ?? $deliveryOrder->status,
            'recorded_at' => now(),
        ]);

        return response()->json($tracking, 201);
    }

    /**
     * Tracking publik tanpa login — outlet cek status kirim pakai nomor DO.
     * Hanya kembalikan info yang aman untuk publik (tidak expose data internal).
     */
    public function publicTrack(string $doNumber)
    {
        $deliveryOrder = DeliveryOrder::where('do_number', $doNumber)
            ->with(['order.outlet:id,name,address', 'trackings' => fn ($q) => $q->latest('recorded_at')->limit(1)])
            ->first();

        if (!$deliveryOrder) {
            return response()->json(['message' => 'Nomor Surat Jalan tidak ditemukan'], 404);
        }

        return response()->json([
            'do_number' => $deliveryOrder->do_number,
            'status' => $deliveryOrder->status,
            'outlet' => $deliveryOrder->order->outlet->name ?? null,
            'shipped_at' => $deliveryOrder->shipped_at,
            'delivered_at' => $deliveryOrder->delivered_at,
            'last_position' => $deliveryOrder->trackings->first(),
        ]);
    }
}
