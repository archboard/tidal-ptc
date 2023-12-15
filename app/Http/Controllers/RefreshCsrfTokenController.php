<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RefreshCsrfTokenController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $request->session()->regenerateToken();

        return response()->json(['token' => csrf_token()]);
    }
}
