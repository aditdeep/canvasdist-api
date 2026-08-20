<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login dan issue Sanctum token.
     * Role yang login menentukan data apa yang bisa diakses (lihat middleware/policy).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif, hubungi admin'], 403);
        }

        // Pastikan setiap user punya wallet (dibuat otomatis kalau belum ada)
        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('wallet', 'memberCard'));
    }

    /**
     * Edit profil sendiri: nama, telepon, dan foto avatar (opsional, upload file
     * multipart). Email & role tidak bisa diubah sendiri — itu wewenang admin.
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'avatar' => 'nullable|image|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $data = $request->only(['name', 'phone', 'address']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = \App\Support\FileUrl::relative($request->file('avatar')->store('avatars', 'public'));
        }

        $user->update($data);

        return response()->json($user->fresh());
    }

    /**
     * Ganti password sendiri (butuh password lama sebagai verifikasi).
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    /**
     * Simpan Expo push token dari device mobile, dipakai untuk kirim notifikasi
     * (mis. saat kurir di-assign pengiriman baru).
     */
    public function registerPushToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'push_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $request->user()->update(['push_token' => $request->push_token]);

        return response()->json(['message' => 'Token tersimpan']);
    }
}
