<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    /**
     * Agen hanya lihat gudang miliknya sendiri.
     */
    public function index(Request $request)
    {
        $query = Warehouse::latest();

        if ($request->user()->role === 'agen') {
            $query->where('agent_id', $request->user()->id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'agent_id' => 'nullable|exists:users,id',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        return response()->json(Warehouse::create($validator->validated()), 201);
    }

    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse->load('stocks.product'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $warehouse->update($request->only(['name', 'agent_id', 'address']));

        return response()->json($warehouse);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json(['message' => 'Gudang dihapus']);
    }
}
