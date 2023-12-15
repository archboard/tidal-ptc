<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (app()->environment('local')) {
    Route::get('scratch', fn () => inertia('Scratch'));
}

/**
 * Self-hosted only routes
 */
Route::middleware(['self_hosted'])
    ->group(function () {
        Route::middleware('uninstalled')
            ->group(function () {
                Route::get('/install', [\App\Http\Controllers\InstallationController::class, 'index']);
                Route::post('/install', [\App\Http\Controllers\InstallationController::class, 'store'])
                    ->name('install');
            });

        Route::middleware(['tenant', 'installed', 'no_admin'])
            ->group(function () {
                Route::get('/install/user', [\App\Http\Controllers\InstallFirstUserController::class, 'index'])
                    ->name('install.user');
                Route::post('/install/user', [\App\Http\Controllers\InstallFirstUserController::class, 'store']);
                Route::post('/search/sis/user', \App\Http\Controllers\Search\SisUserController::class);
            });
    });

Route::middleware('tenant')->group(function () {
    // PowerSchool auth
    Route::middleware(['sis_configured'])
        ->prefix('/auth/powerschool')
        ->group(function () {
            Route::get('/openid', [\App\Http\Controllers\Auth\PowerSchoolOpenIdLoginController::class, 'authenticate']);
            Route::get('/openid/verify', [\App\Http\Controllers\Auth\PowerSchoolOpenIdLoginController::class, 'login'])
                ->name('openid.verify');
            Route::get('/oidc/authenticate', [\App\Http\Controllers\Auth\PowerSchoolOidcLoginController::class, 'authenticate']);
            Route::get('/oidc', [\App\Http\Controllers\Auth\PowerSchoolOidcLoginController::class, 'login']);
        });

    // TODO: ClassLink auth
    Route::prefix('/auth/classlink')
        ->group(function () {
            Route::get('/oauth', [\App\Http\Controllers\ClassLinkOAuthController::class, 'authenticate'])
                ->name('classlink.authenticate');
            Route::get('/redirect', [\App\Http\Controllers\ClassLinkOAuthController::class, 'login']);
        });

    Route::middleware('auth')->group(function () {
        Route::get('/ping', \App\Http\Controllers\CheckAuthStatusController::class)
            ->name('auth.status');

        Route::get('/csrf-token', \App\Http\Controllers\RefreshCsrfTokenController::class)
            ->name('csrf-token');

        Route::get('/timezones', fn () => timezones());

        Route::get('/', function () {
            return inertia('Index');
        })->name('home');

        Route::get('/select-school', [\App\Http\Controllers\SchoolSelectionController::class, 'index'])
            ->name('select-school');
        Route::post('/select-school', [\App\Http\Controllers\SchoolSelectionController::class, 'update']);
        Route::post('/sync/{model}/{id}', \App\Http\Controllers\Settings\SyncModelController::class)
            ->name('model.sync');

        Route::middleware(['has_school'])
            ->group(function () {
                Route::resource('/students', \App\Http\Controllers\StudentController::class)
                    ->only('index', 'show');

                Route::resource('/sections', \App\Http\Controllers\SectionController::class)
                    ->only('index', 'show');

                Route::resource('/courses', \App\Http\Controllers\CourseController::class)
                    ->only('index', 'show');

                Route::resource('/users', \App\Http\Controllers\UserController::class)
                    ->only('index', 'show');
            });

        Route::prefix('/settings')
            ->name('settings.')
            ->group(function () {
                Route::singleton('/personal', \App\Http\Controllers\Settings\PersonalSettingsController::class)
                    ->only('edit', 'update');

                Route::put('/timezone', \App\Http\Controllers\UpdateTimezoneController::class)
                    ->name('timezone.update');

                Route::put('/current-school', \App\Http\Controllers\UpdateCurrentSchoolController::class)
                    ->name('current-school.update');

                Route::middleware('can:edit tenant settings')->group(function () {
                    Route::singleton('/tenant', \App\Http\Controllers\Settings\TenantSettingsController::class)
                        ->only('edit', 'update');

                    Route::put('/tenant/smtp', \App\Http\Controllers\UpdateSmtpSettingsController::class)
                        ->name('tenant.smtp');

                    Route::post('/tenant/smtp/test', \App\Http\Controllers\SendSmtpTestController::class)
                        ->name('tenant.smtp.test');

                    Route::put('/tenant/schools', \App\Http\Controllers\UpdateTenantSchoolsController::class)
                        ->name('tenant.schools');
                });

                Route::middleware(['can:edit school settings', 'has_school'])->group(function () {
                    Route::singleton('/school', \App\Http\Controllers\Settings\SchoolSettingsController::class)
                        ->only('edit', 'update');

                    Route::post('/school/sync/{item}', \App\Http\Controllers\Settings\SyncSchoolItemController::class)
                        ->name('school.item-sync');
                });
            });
    });
});
