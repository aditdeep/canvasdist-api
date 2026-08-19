<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReturnItemController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(ReturnItem::latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $returnitem = ReturnItem::create($validator->validated());

        return response()->json($returnitem, 201);
    }

    public function show(ReturnItem $returnitem)
    {
        return response()->json($returnitem);
    }

    public function update(Request $request, ReturnItem $returnitem)
    {
        $returnitem->update($request->all());

        return response()->json($returnitem);
    }

    public function destroy(ReturnItem $returnitem)
    {
        $returnitem->delete();

        return response()->json(['message' => 'Data dihapus']);
    }
}
