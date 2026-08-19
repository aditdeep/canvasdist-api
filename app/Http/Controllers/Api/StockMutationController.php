<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockMutationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(StockMutation::with('product')->latest()->paginate(20));
    }

    /**
     * Catat mutasi stok (in/out/transfer/adjustment) dan otomatis update tabel stocks.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'type' => 'required|in:in,out,transfer,adjustment',
            'reference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $mutation = DB::transaction(function () use ($request) {
            $mutation = StockMutation::create($request->all());

            if ($request->from_warehouse_id) {
                $stock = Stock::firstOrCreate(
                    ['warehouse_id' => $request->from_warehouse_id, 'product_id' => $request->product_id],
                    ['qty' => 0]
                );

                if ($stock->qty < $request->qty) {
                    throw new \RuntimeException('Stok di gudang asal tidak mencukupi');
                }

                $stock->decrement('qty', $request->qty);
            }

            if ($request->to_warehouse_id) {
                $stock = Stock::firstOrCreate(
                    ['warehouse_id' => $request->to_warehouse_id, 'product_id' => $request->product_id],
                    ['qty' => 0]
                );

                $stock->increment('qty', $request->qty);
            }

            return $mutation;
        });

        return response()->json($mutation, 201);
    }
}
