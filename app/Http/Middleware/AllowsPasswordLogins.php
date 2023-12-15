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
        if (Tenant::current()->allow_password_auth) {
            return $next($request);
        }

        abort(404);
    }
}
