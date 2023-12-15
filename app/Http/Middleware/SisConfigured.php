<?php

namespace App\Http\Middleware;

use App\Exceptions\SisNotConfiguredException;
use Closure;
use Illuminate\Http\Request;

class SisConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->tenant();

        if ($tenant->sis_provider?->isConfigured($tenant->sis_config)) {
            return $next($request);
        }

        throw new SisNotConfiguredException(__('SIS is not configured. Please contact your systems administrator.'));
    }
}
