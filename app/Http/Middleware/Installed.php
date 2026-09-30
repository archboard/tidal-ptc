<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class Installed
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Tenant::current()?->installed()) {
            return $next($request);
        }

        // Cloud districts install through their signed setup link, see Tenant::setupLinks()
        if (config('app.cloud')) {
            return inertia('Error', [
                'status' => 503,
                'message' => __('This district is still being set up. Please contact your administrator.'),
            ])->toResponse($request)->setStatusCode(503);
        }

        // Redirect to installation
        return redirect()->route('install');
    }
}
