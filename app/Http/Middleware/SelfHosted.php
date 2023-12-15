<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SelfHosted
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('app.cloud')) {
            abort(404);
        }

        return $next($request);
    }
}
