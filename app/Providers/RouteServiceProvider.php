<?php

namespace App\Providers;

use App\Http\Controllers\Api\TenantController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            if (app()->environment(['local', 'testing'])) {
                Route::middleware('web')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/testing.php'));
            }

            Route::middleware(['cloud', 'api', 'auth:machine'])
                ->namespace($this->namespace)
                ->prefix('api')
                ->group(function () {
                    Route::get('/tenants', [TenantController::class, 'index']);
                    Route::post('/tenants', [TenantController::class, 'store']);
                    Route::get('/tenants/{tenant:license}', [TenantController::class, 'show']);
                    Route::put('/tenants/{tenant:license}', [TenantController::class, 'update']);
                });
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
