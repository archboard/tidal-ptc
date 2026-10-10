<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the shared demo usable by blocking writes to settings and accounts while app.demo is on.
 */
class PreventInDemo
{
    /** @var list<string> */
    public const array ROUTES = [
        'settings.tenant.*',
        'settings.school.*',
        'settings.personal.*',
        'user-password.update',
        'password.*',
        'users.permissions.*',
        'model.sync',
    ];

    public static function locks(Request $request): bool
    {
        return config('app.demo') && $request->routeIs(self::ROUTES);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() && self::locks($request)) {
            return back()->with('error', __('This is disabled in demo mode.'));
        }

        return $next($request);
    }
}
