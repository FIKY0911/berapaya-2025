<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\EncryptionHelper;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            // ✅ Validate input (login can be phone or email)
            $request->validate([
                'login'    => 'required|string',
                'password' => 'required|string',
            ]);

            $loginInput = $request->login;
            $password   = $request->password;

            // ✅ Determine whether input is email or phone
            if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
                $credentials = ['email' => $loginInput, 'password' => $password];
            } else {
                $credentials = ['phone' => $loginInput, 'password' => $password];
            }

            // ❌ Login failed
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Invalid email/phone or password',
                    'data'      => null,
                    'encrypted' => null
                ], 400);
            }

            // ✅ Login success
            $user = Auth::user();
            $userData = [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            // 🔒 Encrypt user data
            $encryptedData = EncryptionHelper::encrypt(json_encode($userData));

            return response()->json([
                'status'    => 'success',
                'message'   => 'Login successful',
                'data'      => $encryptedData
                // 'data' => $userData,
                // 'encrypted' => $encryptedData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ⚠️ Validation error
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation failed',
                'errors'    => $e->errors(),
                'data'      => null,
                'encrypted' => null
            ], 400);

        } catch (\Exception $e) {
            // 💥 Server error
            Log::error("Login error: " . $e->getMessage());

            return response()->json([
                'status'    => 'error',
                'message'   => 'Internal Server Error',
                'data'      => null,
                'encrypted' => null
            ], 500);
        }
    }
}
