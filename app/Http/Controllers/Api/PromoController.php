<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Promo::latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|in:discount_percent,discount_fixed,tiered,points',
            'value' => 'required|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'target_level' => 'nullable|in:wilayah,agen,reseller,outlet',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $promo = Promo::create($validator->validated());

        return response()->json($promo, 201);
    }

    public function show(Promo $promo)
    {
        return response()->json($promo);
    }

    public function update(Request $request, Promo $promo)
    {
        $promo->update($request->all());

        return response()->json($promo);
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return response()->json(['message' => 'Data dihapus']);
    }
}
