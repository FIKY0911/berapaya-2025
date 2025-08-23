<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Helpers\EncryptionHelper;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile using X-API-KEY.
     */
    public function index(Request $request)
    {
        try {
            $apiKey = $request->header('X-API-KEY');

            if (!$apiKey || $apiKey !== env('API_KEY')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthorized',
                    'data'    => null,
                ], 401);
            }

            // Jika mau, bisa ambil user dari parameter lain (misal user_id)
            $userId = $request->header('X-USER-ID');
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found',
                    'data'    => null,
                ], 404);
            }

            $userData = [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url
                    ? asset('storage/' . $user->avatar_url)
                    : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp&r=g&s=250',
            ];

            $encryptedData = EncryptionHelper::encrypt(json_encode($userData));

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile fetched successfully',
                'data'    => $encryptedData,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Profile error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Internal Server Error',
                'data'    => null
            ], 500);
        }
    }

    /**
     * Update profile using X-API-KEY.
     */
    public function update(Request $request)
    {
        try {
            $apiKey = $request->header('X-API-KEY');

            if (!$apiKey || $apiKey !== env('API_KEY')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthorized',
                    'data'    => null,
                ], 401);
            }

            $userId = $request->header('X-USER-ID');
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found',
                    'data'    => null,
                ], 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255',
                'avatar' => 'nullable|image|max:2048',
            ]);

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar_url = $path;
            }

            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            $user->save();

            $userData = [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url
                    ? asset('storage/' . $user->avatar_url)
                    : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp&r=g&s=250',
            ];

            $encryptedData = EncryptionHelper::encrypt(json_encode($userData));

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile updated successfully',
                'data'    => $encryptedData,
                // 'data' => $userData,
                // 'encrypted' => $encryptedData
            ], 200);

        } catch (\Exception $e) {
            Log::error("Profile update error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Internal Server Error',
                'data'    => null,
            ], 500);
        }
    }
}
