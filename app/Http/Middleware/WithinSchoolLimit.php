<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Enums\UserType;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks staff from the app while a cloud district has more active schools than it's
 * licensed for, e.g. after a renewal lowered `school_limit`. Guardians and students keep
 * booking, and settings stay reachable so a district admin can fix it.
 */
class WithinSchoolLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        $tenant = Tenant::current();
        $limit = $tenant?->school_limit;

        if (! config('app.cloud') || $limit === null || $user?->user_type !== UserType::staff) {
            return $next($request);
        }

        // ponytail: one count per staff request; cache per tenant if it shows up in profiling
        $active = $tenant->schools()->where('active', true)->count();

        if ($active <= $limit) {
            return $next($request);
        }

        $isAdmin = $user->can(Permission::editTenantSettings->value);
        $message = $isAdmin
            ? __('Your district has :active active schools but is licensed for :limit. To resolve this, add school licenses, or deactivate schools in Tenant settings.', compact('active', 'limit'))
            : __('Tidal PTC is unavailable for your district right now. Please contact your district administrator.');

        if ($request->expectsJson() && ! $request->inertia()) {
            abort(402, $message);
        }

        $links = $isAdmin ? array_filter([
            ['label' => __('Tenant settings'), 'href' => route('settings.tenant.edit')],
            config('services.billing.url') ? [
                'label' => __('Request more licenses'),
                'href' => config('services.billing.url').'/quotes/create?'.http_build_query(['product' => 'tidal_ptc', 'schools' => $active]),
            ] : null,
        ]) : [];

        return inertia('Error', [
            'status' => 402,
            'message' => $message,
            'links' => array_values($links),
        ])->toResponse($request)->setStatusCode(402);
    }
}
