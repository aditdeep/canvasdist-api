<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'parent_id', 'region_code', 'is_active']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,wilayah,agen,reseller,sales,gudang,kurir',
            'parent_id' => 'nullable|exists:users,id',
            'region_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            ...$validator->validated(),
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('wallet', 'memberCard'));
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->only(['name', 'phone', 'role', 'parent_id', 'region_code', 'is_active', 'address']));

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->update(['is_active' => false]);

        return response()->json(['message' => 'User dinonaktifkan']);
    }
}
