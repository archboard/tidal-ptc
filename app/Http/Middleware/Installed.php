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
        if (config('app.cloud') || Tenant::current()?->installed()) {
            return $next($request);
        }

        // Redirect to installation
        return redirect()->route('install');
    }
}
