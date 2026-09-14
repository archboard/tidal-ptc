<?php

use App\Http\Controllers\Api\TenantController;
use App\Http\Middleware\AllowsPasswordLogins;
use App\Http\Middleware\Cloud;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HasSchoolSet;
use App\Http\Middleware\Installed;
use App\Http\Middleware\NoDistrictAdmin;
use App\Http\Middleware\ScopeBouncerToSchool;
use App\Http\Middleware\SelfHosted;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SisConfigured;
use App\Http\Middleware\Uninstalled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['cloud', 'api', 'auth:machine'])
                ->prefix('api')
                ->group(function () {
                    Route::get('/tenants', [TenantController::class, 'index']);
                    Route::post('/tenants', [TenantController::class, 'store']);
                });
        },
    )
    // Channel auth needs the tenant session resolved before `auth` runs
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'tenant', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        // Inertia must run last so every earlier middleware can still share props
        $middleware->web(append: [SetLocale::class, HandleInertiaRequests::class]);

        $middleware->group('tenant', [
            Installed::class,
            NeedsTenant::class,
            EnsureValidTenantSession::class,
        ]);

        $middleware->alias([
            'self_hosted' => SelfHosted::class,
            'cloud' => Cloud::class,
            'allows_pw_auth' => AllowsPasswordLogins::class,
            'sis_configured' => SisConfigured::class,
            'uninstalled' => Uninstalled::class,
            'installed' => Installed::class,
            'has_school' => HasSchoolSet::class,
            'scoped_permissions' => ScopeBouncerToSchool::class,
            'no_admin' => NoDistrictAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (NoCurrentTenant $e) => abort(404));

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (
                app()->environment('production')
                && in_array($response->getStatusCode(), [500, 503, 404, 403])
                && (! $request->wantsJson() || $request->inertia())
            ) {
                $title = __('Error');

                return inertia('Error', [
                    'status' => $response->getStatusCode(),
                    'title' => $title,
                ])
                    ->withViewData(compact('title'))
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })
    ->create();
