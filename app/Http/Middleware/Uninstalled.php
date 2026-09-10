<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Uninstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            if ($user->cant('install')) {
                abort(404);
            }
        }

        $tenant = $request->tenant();

        if ($tenant->installed() && $tenant->users()->exists()) {
            abort(404);
        }

        return $next($request);
    }
}
