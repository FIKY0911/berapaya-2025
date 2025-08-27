<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\EncryptionHelper;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        // No token/session to invalidate since X-API-KEY is static
        $response = [
            'status'  => 'success',
            'message' => 'Logout successful - please remove stored API key or user data on client side'
        ];

        // Encrypt for consistency with your API
        $encrypted = EncryptionHelper::encrypt(json_encode($response));

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout successful',
            'data'    => $encrypted
        ], 200);
    }
}
            