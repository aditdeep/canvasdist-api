<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Category::orderBy('sort_order')->orderBy('name')->paginate(50));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'sort_order' => 'nullable|integer',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'sort_order']);

        if ($request->hasFile('image')) {
            $data['image_path'] = FileUrl::relative($request->file('image')->store('categories', 'public'));
        }

        return response()->json(Category::create($data), 201);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->only(['name', 'sort_order', 'is_active']);

        if ($request->hasFile('image')) {
            $data['image_path'] = FileUrl::relative($request->file('image')->store('categories', 'public'));
        }

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Kategori dihapus']);
    }
}
