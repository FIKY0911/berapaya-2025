<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    // GET /api/profile → ambil data user dari email
    public function show(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ], 200);
    }

    // PUT /api/profile → update nama dan email
    public function update(Request $request)
    {
        $request->validate([
            'email'      => 'required|email|exists:users,email',
            'name'       => 'nullable|string|max:255',
            'new_email'  => 'nullable|email|unique:users,email'
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        // Siapkan data untuk update
        $updateData = [];
        if ($request->filled('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->filled('new_email')) {
            $updateData['email'] = $request->new_email;
        }

        if (empty($updateData)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No data provided to update',
            ], 400);
        }

        $user->update($updateData);
        $user = $user->fresh(); // ambil data terbaru

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'data'    => $user->only(['id', 'name', 'email', 'created_at', 'updated_at']),
        ], 200);
    }

    // PUT /api/profile/password → update password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'email'             => 'required|email|exists:users,email',
            'current_password'  => 'required|string',
            'new_password'      => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password updated successfully',
        ], 200);
    }
}
