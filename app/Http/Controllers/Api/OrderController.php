<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CommissionService;
use App\Services\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected PromoService $promoService,
        protected CommissionService $commissionService,
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['outlet', 'agent', 'items.product', 'deliveryOrder', 'paymentTransactions'])->latest();

        if ($request->user()->isRole('agen', 'wilayah')) {
            $query->where('agent_id', $request->user()->id);
        } elseif ($request->user()->role === 'customer') {
            $query->where('outlet_id', $request->user()->outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Buat order baru — biasanya dari hasil canvasing (visit_id ada),
     * tapi bisa juga order langsung tanpa kunjungan, atau order storefront
     * dari customer (outlet_id & agent_id otomatis dari akun customer,
     * tidak bisa diisi manual dari request demi keamanan).
     */
    public function store(Request $request)
    {
        $isCustomer = $request->user()->role === 'customer';

        $validator = Validator::make($request->all(), [
            'visit_id' => 'nullable|exists:visits,id',
            'outlet_id' => $isCustomer ? 'nullable' : 'required|exists:outlets,id',
            'agent_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:cash,saldo,duitku',
            'fulfillment_type' => 'nullable|in:delivery,pickup',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        if ($isCustomer && !$request->user()->outlet_id) {
            return response()->json(['message' => 'Akun kamu belum punya alamat pengiriman terdaftar.'], 422);
        }

        $order = DB::transaction(function () use ($request, $isCustomer) {
            $outletId = $isCustomer ? $request->user()->outlet_id : $request->outlet_id;
            $agentId = $isCustomer ? $request->user()->outlet->agent_id : $request->agent_id;

            $order = Order::create([
                'order_no' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'visit_id' => $request->visit_id,
                'outlet_id' => $outletId,
                'agent_id' => $agentId,
                'payment_method' => $request->payment_method ?? 'cash',
                'fulfillment_type' => $request->fulfillment_type ?? 'delivery',
                'is_storefront_order' => $isCustomer,
                'status' => 'pending',
            ]);

            $subtotal = 0;
            $discountTotal = 0;

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (int) $item['qty'];
                // Customer storefront selalu pakai harga level 'reseller' (harga retail-facing),
                // konsisten dengan yang ditampilkan di katalog publik.
                $priceLevel = $isCustomer ? 'reseller' : ($request->user()->role ?? 'reseller');
                $price = $product->priceForLevel($priceLevel);
                $discount = $this->promoService->calculateItemDiscount($qty, $price, $priceLevel);
                $lineSubtotal = ($qty * $price) - $discount;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'subtotal' => $lineSubtotal,
                ]);

                $subtotal += $qty * $price;
                $discountTotal += $discount;
            }

            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal - $discountTotal,
            ]);

            return $order;
        });

        return response()->json($order->load('items.product', 'outlet'), 201);
    }

    public function show(Order $order)
    {
        return response()->json($order->load('items.product', 'outlet', 'agent', 'deliveryOrder', 'invoice'));
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->only(['status', 'payment_method']));

        return response()->json($order);
    }

    /**
     * Approve order: kurangi stok gudang milik agen terkait, lanjutkan ke
     * gudang untuk packing. Best-effort — kalau data stok belum lengkap
     * (gudang belum diisi/qty belum di-set), approval tetap jalan supaya
     * nggak macetin operasional, tapi mutasi stok dicatat untuk yang tersedia.
     * Saat order berstatus 'completed', baru komisi jaringan dihitung — lihat markCompleted().
     */
    public function approve(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order tidak dalam status pending'], 422);
        }

        DB::transaction(function () use ($order) {
            $warehouse = $order->agent_id
                ? \App\Models\Warehouse::where('agent_id', $order->agent_id)->first()
                : null;

            if ($warehouse) {
                foreach ($order->items()->with('product')->get() as $item) {
                    $stock = \App\Models\Stock::firstOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $item->product_id],
                        ['qty' => 0]
                    );

                    $stock->decrement('qty', $item->qty);

                    \App\Models\StockMutation::create([
                        'from_warehouse_id' => $warehouse->id,
                        'to_warehouse_id' => null,
                        'product_id' => $item->product_id,
                        'qty' => $item->qty,
                        'type' => 'out',
                        'reference' => $order->order_no,
                    ]);
                }
            }

            $order->update(['status' => 'approved']);
        });

        return response()->json($order->fresh());
    }

    /**
     * Dipanggil setelah POD (pengiriman) diterima — lihat DeliveryOrderController@uploadPod.
     * Trigger perhitungan komisi jaringan berjenjang.
     */
    public function markCompleted(Order $order)
    {
        if ($order->status === 'completed') {
            return response()->json($order);
        }

        $order->update(['status' => 'completed']);
        $this->commissionService->generateForOrder($order);

        return response()->json($order->load('commissions'));
    }

    /**
     * Selesaikan order fulfillment_type=pickup — dipakai agen/gudang saat
     * customer sudah datang ambil barang di outlet, tanpa lewat proses
     * kurir/DeliveryOrder sama sekali (beda dari alur "diantar").
     */
    public function completePickup(Order $order)
    {
        if ($order->fulfillment_type !== 'pickup') {
            return response()->json(['message' => 'Order ini bukan tipe ambil sendiri'], 422);
        }

        if ($order->status !== 'approved') {
            return response()->json(['message' => 'Order harus berstatus approved dulu sebelum ditandai diambil'], 422);
        }

        return $this->markCompleted($order);
    }

    public function destroy(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Hanya order pending yang bisa dihapus'], 422);
        }

        $order->delete();

        return response()->json(['message' => 'Order dihapus']);
    }
}
