<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Support\FileUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Info branding publik (nama app, logo) — dipakai navbar storefront &
     * sidebar dashboard, tidak perlu login.
     */
    public function public()
    {
        $settings = Settings::current();

        return response()->json([
            'app_name' => $settings->app_name,
            'logo_path' => $settings->logo_path,
            'owner_name' => $settings->owner_name,
            'owner_email' => $settings->owner_email,
            'owner_phone' => $settings->owner_phone,
        ]);
    }

    /**
     * Config lengkap untuk halaman Pengaturan admin (super_admin saja).
     */
    public function show()
    {
        return response()->json(Settings::current()->load('platformOwner'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'sometimes|required|string|max:60',
            'owner_name' => 'nullable|string',
            'owner_email' => 'nullable|email',
            'owner_phone' => 'nullable|string',
            'platform_fee_percent' => 'nullable|numeric|min:0|max:100',
            'platform_owner_user_id' => 'nullable|exists:users,id',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $settings = Settings::current();
        $data = $request->only([
            'app_name', 'owner_name', 'owner_email', 'owner_phone',
            'platform_fee_percent', 'platform_owner_user_id',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = FileUrl::relative($request->file('logo')->store('branding', 'public'));
        }

        $settings->update($data);

        return response()->json($settings->fresh()->load('platformOwner'));
    }
}
