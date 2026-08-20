<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::with('outlet')->latest('visited_at');

        // Sales cuma lihat kunjungan sendiri, admin/agen lihat semua di wilayahnya
        if ($request->user()->isRole('sales')) {
            $query->where('sales_id', $request->user()->id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Cek-in kunjungan sales ke outlet.
     * Wajib GPS. Foto etalase/stok opsional (upload file beneran, disimpan ke storage).
     */
    public function checkin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'outlet_id' => 'required|exists:outlets,id',
            'checkin_lat' => 'required|numeric|between:-90,90',
            'checkin_lng' => 'required|numeric|between:-180,180',
            'photo' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $photoPath = $request->hasFile('photo')
            ? \App\Support\FileUrl::relative($request->file('photo')->store('visits', 'public'))
            : null;

        $visit = Visit::create([
            'sales_id' => $request->user()->id,
            'outlet_id' => $request->outlet_id,
            'checkin_lat' => $request->checkin_lat,
            'checkin_lng' => $request->checkin_lng,
            'photo_path' => $photoPath,
            'notes' => $request->notes,
            'visited_at' => now(),
        ]);

        return response()->json($visit->load('outlet'), 201);
    }

    public function show(Visit $visit)
    {
        return response()->json($visit->load('outlet', 'sales', 'order'));
    }

    public function update(Request $request, Visit $visit)
    {
        $visit->update($request->only(['notes', 'photo_path']));

        return response()->json($visit);
    }

    public function destroy(Visit $visit)
    {
        $visit->delete();

        return response()->json(['message' => 'Kunjungan dihapus']);
    }
}
