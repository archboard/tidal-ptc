<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoDistrictAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenant::current()->users()->whereIs(Role::DISTRICT_ADMIN->value)->doesntExist()) {
            return $next($request);
        }

        session()->flash('error', __('A district admin already exists.'));

        return back();
    }
}
