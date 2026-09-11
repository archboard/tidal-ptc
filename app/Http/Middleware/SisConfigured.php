<?php

namespace App\Http\Middleware;

use App\Exceptions\SisNotConfiguredException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SisConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
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
