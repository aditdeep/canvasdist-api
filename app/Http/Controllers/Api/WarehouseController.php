<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Warehouse::latest()->paginate(20));
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
