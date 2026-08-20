<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Product::with('categoryModel')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'sku' => 'required|string|unique:products,sku',
            'category' => 'nullable|string',
            'unit' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'sku', 'category', 'category_id', 'unit', 'base_price', 'description']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = \App\Support\FileUrl::relative($request->file('photo')->store('products', 'public'));
        }

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->only(['name', 'sku', 'category', 'category_id', 'unit', 'base_price', 'description', 'is_active']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = \App\Support\FileUrl::relative($request->file('photo')->store('products', 'public'));
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Data dihapus']);
    }
}
