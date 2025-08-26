<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictController extends Controller
{
    public function predict(Request $request)
    {
        $validated = $request->validate([
            'penyakit' => 'required|string',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'radius_km' => 'required|numeric',
        ]);

        $baseUrl = env('API_BASE_URL'); // isi di .env misal: http://127.0.0.1:8000
        $response = Http::get("$baseUrl/predict", $validated); // jika mau GET
        // $response = Http::post("$baseUrl/predict", $validated); // jika mau POST

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memanggil API Python.'
        ], 500);
    }
}
