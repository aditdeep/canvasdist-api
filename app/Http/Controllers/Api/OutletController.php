<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutletController extends Controller
{
    /**
     * Agen & sales hanya lihat outlet di jaringan mereka sendiri (agen: outlet miliknya,
     * sales: outlet milik agen atasannya).
     */
    public function index(Request $request)
    {
        $query = Outlet::latest();
        $user = $request->user();

        if ($user->role === 'agen') {
            $query->where('agent_id', $user->id);
        } elseif ($user->role === 'sales' && $user->parent_id) {
            $query->where('agent_id', $user->parent_id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Sales yang menambahkan outlet baru saat canvasing otomatis terdaftar di bawah
     * agen atasannya, bukan agen sembarangan dari input.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'owner_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'agent_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = $request->user();

        if ($user->role === 'sales') {
            $data['agent_id'] = $user->parent_id;
        } elseif ($user->role === 'agen') {
            $data['agent_id'] = $user->id;
        }

        $outlet = Outlet::create($data);

        return response()->json($outlet, 201);
    }

    public function show(Outlet $outlet)
    {
        return response()->json($outlet);
    }

    public function update(Request $request, Outlet $outlet)
    {
        $outlet->update($request->all());

        return response()->json($outlet);
    }

    public function destroy(Outlet $outlet)
    {
        $outlet->delete();

        return response()->json(['message' => 'Data dihapus']);
    }
}
