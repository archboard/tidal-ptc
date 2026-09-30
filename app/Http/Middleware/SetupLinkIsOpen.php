<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the signed links from Tenant::setupLinks(). The signed tenant id must match the
 * tenant the domain resolves to, and the links close once a district admin exists.
 */
class SetupLinkIsOpen
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->tenant();

        abort_unless($tenant->id === (int) $request->route('tenant') && ! $tenant->hasDistrictAdmin(), 404);

        return $next($request);
    }
}
