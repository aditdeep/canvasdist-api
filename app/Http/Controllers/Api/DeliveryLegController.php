<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLeg;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryLegController extends Controller
{
    public function index(DeliveryOrder $deliveryOrder)
    {
        return response()->json($deliveryOrder->legs()->with('fromHub', 'toHub', 'courier')->get());
    }

    /**
     * Definisikan rute multi-hub untuk sebuah Surat Jalan. Dikirim sekaligus
     * sebagai array etape berurutan. Etape terakhir boleh punya to_hub_id=null
     * (artinya langsung last-mile ke outlet, dilanjut lewat flow status/POD biasa).
     *
     * Body contoh:
     * { "legs": [
     *     { "from_hub_id": 1, "to_hub_id": 2, "courier_id": 5 },
     *     { "from_hub_id": 2, "to_hub_id": null, "courier_id": 8 }
     * ]}
     */
    public function store(Request $request, DeliveryOrder $deliveryOrder)
    {
        $validator = Validator::make($request->all(), [
            'legs' => 'required|array|min:1',
            'legs.*.from_hub_id' => 'nullable|exists:hubs,id',
            'legs.*.to_hub_id' => 'nullable|exists:hubs,id',
            'legs.*.courier_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Hapus rute lama kalau ada (redefine dari awal), lalu buat baru berurutan
        $deliveryOrder->legs()->delete();

        foreach ($request->legs as $i => $leg) {
            DeliveryLeg::create([
                'delivery_order_id' => $deliveryOrder->id,
                'sequence' => $i + 1,
                'from_hub_id' => $leg['from_hub_id'] ?? null,
                'to_hub_id' => $leg['to_hub_id'] ?? null,
                'courier_id' => $leg['courier_id'] ?? null,
                'status' => 'pending',
            ]);
        }

        $deliveryOrder->update(['status' => 'siap_kirim']);

        return response()->json($deliveryOrder->load('legs.fromHub', 'legs.toHub', 'legs.courier'), 201);
    }

    /**
     * Kurir etape ini mulai berangkat dari hub asal.
     */
    public function start(DeliveryLeg $leg)
    {
        if ($leg->status !== 'pending') {
            return response()->json(['message' => 'Etape ini sudah berjalan/selesai'], 422);
        }

        $leg->update(['status' => 'in_transit', 'departed_at' => now()]);
        $leg->deliveryOrder->update([
            'status' => 'dikirim',
            'courier_id' => $leg->courier_id,
            'shipped_at' => $leg->deliveryOrder->shipped_at ?? now(),
        ]);

        return response()->json($leg->fresh()->load('fromHub', 'toHub', 'courier'));
    }

    /**
     * Kurir etape ini sampai di hub tujuan (serah terima). Kalau ada etape
     * berikutnya, DO berstatus 'di_hub' menunggu kurir etape berikutnya mulai.
     * Kalau etape ini last-mile langsung ke outlet, DO lanjut ke 'sampai_tujuan'
     * seperti alur biasa (tinggal upload POD).
     */
    public function arrive(DeliveryLeg $leg)
    {
        if ($leg->status !== 'in_transit') {
            return response()->json(['message' => 'Etape ini belum berjalan'], 422);
        }

        $leg->update(['status' => 'arrived', 'arrived_at' => now()]);

        $nextLeg = $leg->deliveryOrder->legs()->where('sequence', '>', $leg->sequence)->first();
        $deliveryOrder = $leg->deliveryOrder;

        if ($nextLeg) {
            $deliveryOrder->update(['status' => 'di_hub']);
        } elseif ($leg->isFinalMile()) {
            $deliveryOrder->update(['status' => 'sampai_tujuan']);
        } else {
            $deliveryOrder->update(['status' => 'di_hub']);
        }

        return response()->json($leg->fresh()->load('fromHub', 'toHub', 'courier'));
    }
}
