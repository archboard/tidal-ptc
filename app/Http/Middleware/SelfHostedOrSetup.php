<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The install wizard is open when self-hosted, or in the cloud to a session
 * that arrived through this tenant's signed setup link.
 */
class SelfHostedOrSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $inSetup = Tenant::current() !== null
            && $request->session()->get('setup_tenant_id') === Tenant::current()->id;

        abort_unless(config('app.self_hosted') || $inSetup, 404);

        return $next($request);
    }
}
