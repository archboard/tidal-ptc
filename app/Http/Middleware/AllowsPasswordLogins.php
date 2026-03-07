<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class AllowsPasswordLogins
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::current();

        if ($tenant->allow_password_auth || $request->routeIs('login') || $request->routeIs('logout')) {
            return $next($request);
        }

        abort(404);
    }
}
