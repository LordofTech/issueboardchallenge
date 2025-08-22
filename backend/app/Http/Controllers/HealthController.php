<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toDateTimeString(),
            'version' => '1.0.0'
        ]);
    }
}