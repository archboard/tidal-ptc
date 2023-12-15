<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Uninstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            if ($user->cant('install')) {
                abort(404);
            }
        }

        if ($tenant = $request->tenant()) {
            if ($tenant->installed()) {
                abort(404);
            }
        }

        return $next($request);
    }
}
