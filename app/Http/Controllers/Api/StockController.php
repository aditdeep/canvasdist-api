<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Agen/wilayah hanya lihat stok di gudang miliknya sendiri. Super admin, gudang,
     * dan role lain (mis. sales/kurir yang butuh cek ketersediaan) lihat semua.
     */
    public function index(Request $request)
    {
        $query = Stock::with('warehouse', 'product')->latest();

        if ($request->user()->role === 'agen') {
            $query->whereHas('warehouse', fn ($w) => $w->where('agent_id', $request->user()->id));
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $stock = Stock::create($validator->validated());

        return response()->json($stock, 201);
    }

    public function show(Stock $stock)
    {
        return response()->json($stock);
    }

    public function update(Request $request, Stock $stock)
    {
        $stock->update($request->all());

        return response()->json($stock);
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();

        return response()->json(['message' => 'Data dihapus']);
    }
}
