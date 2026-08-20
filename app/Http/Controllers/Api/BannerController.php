<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Banner::orderBy('sort_order')->paginate(50));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'subtitle' => 'nullable|string',
            'link_url' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'image' => 'nullable|image|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['title', 'subtitle', 'link_url', 'sort_order']);

        if ($request->hasFile('image')) {
            $data['image_path'] = FileUrl::relative($request->file('image')->store('banners', 'public'));
        }

        return response()->json(Banner::create($data), 201);
    }

    public function show(Banner $banner)
    {
        return response()->json($banner);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->only(['title', 'subtitle', 'link_url', 'sort_order', 'is_active']);

        if ($request->hasFile('image')) {
            $data['image_path'] = FileUrl::relative($request->file('image')->store('banners', 'public'));
        }

        $banner->update($data);

        return response()->json($banner);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->json(['message' => 'Banner dihapus']);
    }
}
