<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HubController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Hub::with('warehouse', 'agent')->latest()->paginate(50));
    }

    /**
     * Hub bisa dibuat dari gudang yang sudah ada (type=warehouse, isi warehouse_id),
     * dari kantor agen (type=agent_office, isi agent_id), atau titik custom bebas
     * (type=custom, isi name+address manual).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|in:warehouse,agent_office,custom',
            'warehouse_id' => 'nullable|exists:warehouses,id|required_if:type,warehouse',
            'agent_id' => 'nullable|exists:users,id|required_if:type,agent_office',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        return response()->json(Hub::create($validator->validated()), 201);
    }

    public function show(Hub $hub)
    {
        return response()->json($hub->load('warehouse', 'agent'));
    }

    public function update(Request $request, Hub $hub)
    {
        $hub->update($request->only(['name', 'address', 'latitude', 'longitude']));

        return response()->json($hub);
    }

    public function destroy(Hub $hub)
    {
        $hub->delete();

        return response()->json(['message' => 'Hub dihapus']);
    }
}
