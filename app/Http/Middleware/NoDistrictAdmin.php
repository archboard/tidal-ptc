<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoDistrictAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::current();

        if (! $tenant->hasDistrictAdmin()) {
            return $next($request);
        }

        session()->flash('error', __('A district admin already exists.'));

        return back();
    }
}
