<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardTestController extends Controller
{
    /**
     * Simple dashboard test endpoint
     */
    public function index(Request $request)
    {
        // Log all request details
        Log::info('Dashboard test endpoint accessed', [
            'headers' => $request->headers->all(),
            'token' => $request->bearerToken(),
            'user' => auth()->user()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard test endpoint accessed successfully',
            'data' => [
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'roles' => auth()->user()->roles->pluck('name')
            ]
        ]);
    }
}
