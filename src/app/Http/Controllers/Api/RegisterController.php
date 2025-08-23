<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Helpers\EncryptionHelper;
use Exception;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validate input
            $emailRule = $request->email ? 'email|unique:users,email' : '';
            $phoneRule = $request->phone ? 'string|max:20|unique:users,phone' : '';

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => $emailRule,
                'phone' => $phoneRule,
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Ensure at least email or phone is provided
            if (!$request->email && !$request->phone) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Either email or phone must be provided',
                    'data' => null
                ], 400);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            // Encrypt response
            $encrypted = EncryptionHelper::encrypt(json_encode($userData));

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => $encrypted
                // 'data' => $userData,
                // 'encrypted' => $encrypted
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
