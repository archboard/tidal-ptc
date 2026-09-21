<?php

use App\Enums\Permission;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AssignTranslatorController;
use App\Http\Controllers\Auth\PowerSchoolOidcLoginController;
use App\Http\Controllers\Auth\PowerSchoolOpenIdLoginController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BatchEventSourceController;
use App\Http\Controllers\CheckAuthStatusController;
use App\Http\Controllers\ClassLinkOAuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeleteBatchTimeSlotController;
use App\Http\Controllers\DownloadPowerSchoolPluginController;
use App\Http\Controllers\GetLanguagesController;
use App\Http\Controllers\GetSelectionController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\InstallFirstUserController;
use App\Http\Controllers\RefreshCsrfTokenController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SchoolEventSourceController;
use App\Http\Controllers\SchoolSelectionController;
use App\Http\Controllers\Search\SisUserController;
use App\Http\Controllers\SearchModelController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SendSmtpTestController;
use App\Http\Controllers\Settings\NotificationPreferencesController;
use App\Http\Controllers\Settings\PersonalSettingsController;
use App\Http\Controllers\Settings\SaveSchoolLanguagesController;
use App\Http\Controllers\Settings\SchoolSettingsController;
use App\Http\Controllers\Settings\SyncModelController;
use App\Http\Controllers\Settings\SyncSchoolItemController;
use App\Http\Controllers\Settings\TenantSettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentEventSourceController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\ToggleHiddenController;
use App\Http\Controllers\ToggleSelectionController;
use App\Http\Controllers\TranslatorController;
use App\Http\Controllers\TranslatorRequestController;
use App\Http\Controllers\UpdateCurrentSchoolController;
use App\Http\Controllers\UpdateSelectionVisibilityController;
use App\Http\Controllers\UpdateSmtpSettingsController;
use App\Http\Controllers\UpdateTenantSchoolsController;
use App\Http\Controllers\UpdateTimezoneController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEventSourceController;
use App\Http\Controllers\UserPermissionController;
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

/**
 * Self-hosted only routes
 */
Route::middleware(['self_hosted'])
    ->group(function () {
        Route::middleware('uninstalled')
            ->group(function () {
                Route::get('/install', [InstallationController::class, 'index']);
                Route::post('/install', [InstallationController::class, 'store'])
                    ->name('install');
                Route::get('/install/plugin', DownloadPowerSchoolPluginController::class)
                    ->name('install.plugin');
            });

        Route::middleware(['tenant', 'installed', 'no_admin'])
            ->group(function () {
                Route::get('/install/user', [InstallFirstUserController::class, 'index'])
                    ->name('install.user');
                Route::post('/install/user', [InstallFirstUserController::class, 'store']);
                Route::post('/search/sis/user', SisUserController::class);
            });
    });

Route::middleware('tenant')->group(function () {
    // PowerSchool auth
    Route::middleware(['sis_configured'])
        ->prefix('/auth/powerschool')
        ->group(function () {
            Route::get('/openid', [PowerSchoolOpenIdLoginController::class, 'authenticate']);
            Route::get('/openid/verify', [PowerSchoolOpenIdLoginController::class, 'login'])
                ->name('openid.verify');
            Route::get('/oidc/authenticate', [PowerSchoolOidcLoginController::class, 'authenticate']);
            Route::get('/oidc', [PowerSchoolOidcLoginController::class, 'login']);
        });

    // TODO: ClassLink auth
    Route::prefix('/auth/classlink')
        ->group(function () {
            Route::get('/oauth', [ClassLinkOAuthController::class, 'authenticate'])
                ->name('classlink.authenticate');
            Route::get('/redirect', [ClassLinkOAuthController::class, 'login']);
        });

    Route::middleware('auth')->group(function () {
        Route::get('/ping', CheckAuthStatusController::class)
            ->name('auth.status');

        Route::get('/csrf-token', RefreshCsrfTokenController::class)
            ->name('csrf-token');

        Route::get('/timezones', fn () => timezones());
        Route::get('/languages', GetLanguagesController::class);

        Route::get('/select-school', [SchoolSelectionController::class, 'index'])
            ->name('select-school');
        Route::post('/select-school', [SchoolSelectionController::class, 'update']);
        Route::post('/sync/{model}/{id}', SyncModelController::class)
            ->name('model.sync');
        Route::post('/search/{model}', SearchModelController::class)
            ->name('model.search');

        Route::match(['get', 'post'], '/schools/{school}/event-source', SchoolEventSourceController::class)
            ->name('schools.event-source');

        Route::middleware(['has_school', 'scoped_permissions'])
            ->group(function () {
                Route::match(['post', 'delete'], '/selection/{model}', ToggleSelectionController::class)
                    ->name('selection.toggle');
                Route::get('/selection/{model}', GetSelectionController::class)
                    ->name('selection.get');
                Route::post('/selection/{model}/hidden', UpdateSelectionVisibilityController::class)
                    ->name('selection.hidden');
                Route::put('/toggle-hidden', ToggleHiddenController::class)
                    ->name('toggle-hidden');

                Route::get('/teachers', TeacherController::class)
                    ->name('teachers.index');

                Route::resource('/batches', BatchController::class);
                Route::post('/batches/{batch}/delete', DeleteBatchTimeSlotController::class);
                Route::match(['get', 'post'], '/batches/{batch}/event-source', BatchEventSourceController::class)
                    ->name('batches.event-source');

                Route::resource('/time-slots', TimeSlotController::class)
                    ->only('index', 'create', 'store', 'update', 'destroy');

                Route::get('/', DashboardController::class)
                    ->name('home');

                Route::get('/translators', [TranslatorRequestController::class, 'index'])
                    ->name('translators.index');
                Route::resource('/translator-profiles', TranslatorController::class)
                    ->only('index', 'store', 'update', 'destroy');
                Route::put('/time-slots/{time_slot}/translator', AssignTranslatorController::class)
                    ->name('time-slots.translator');

                Route::get('/activity', [ActivityController::class, 'index'])
                    ->name('activity.index');
                Route::get('/time-slots/{time_slot}/activity', [ActivityController::class, 'timeSlot'])
                    ->name('time-slots.activity');

                Route::get('/reservations/create/{student}/{user}', [ReservationController::class, 'create'])
                    ->name('reservations.create');
                Route::post('/reservations/{time_slot}', [ReservationController::class, 'store'])
                    ->name('reservations.store');
                Route::put('/reservations/{time_slot}', [ReservationController::class, 'update'])
                    ->name('reservations.update');
                Route::delete('/reservations/{time_slot}', [ReservationController::class, 'destroy'])
                    ->name('reservations.destroy');

                Route::resource('/students', StudentController::class)
                    ->only('index', 'show');
                Route::match(['get', 'post'], '/students/{student}/event-source', StudentEventSourceController::class)
                    ->name('students.event-source');

                Route::resource('/sections', SectionController::class)
                    ->only('index', 'show', 'edit', 'update');

                Route::resource('/courses', CourseController::class)
                    ->only('index', 'show');

                Route::resource('/users', UserController::class)
                    ->only('index', 'show', 'edit');
                Route::match(['get', 'post'], '/users/{user}/event-source', UserEventSourceController::class)
                    ->name('users.event-source');

                Route::middleware(Permission::editPermissions->toMiddleware())
                    ->group(function () {
                        Route::get('/users/{user}/permissions', [UserPermissionController::class, 'index'])
                            ->name('users.permissions.index');
                        Route::put('/users/{user}/permissions', [UserPermissionController::class, 'update'])
                            ->name('users.permissions.update');
                    });
            });

        Route::prefix('/settings')
            ->name('settings.')
            ->group(function () {
                Route::singleton('/personal', PersonalSettingsController::class)
                    ->only('edit', 'update');

                Route::put('/personal/notifications', NotificationPreferencesController::class)
                    ->name('personal.notifications');

                Route::put('/timezone', UpdateTimezoneController::class)
                    ->name('timezone.update');

                Route::put('/current-school', UpdateCurrentSchoolController::class)
                    ->name('current-school.update');

                Route::middleware('can:edit tenant settings')->group(function () {
                    Route::singleton('/tenant', TenantSettingsController::class)
                        ->only('edit', 'update');

                    Route::put('/tenant/smtp', UpdateSmtpSettingsController::class)
                        ->name('tenant.smtp');

                    Route::post('/tenant/smtp/test', SendSmtpTestController::class)
                        ->name('tenant.smtp.test');

                    Route::put('/tenant/schools', UpdateTenantSchoolsController::class)
                        ->name('tenant.schools');

                    Route::get('/tenant/plugin', DownloadPowerSchoolPluginController::class)
                        ->name('tenant.plugin');
                });

                Route::middleware(['has_school', 'scoped_permissions', 'can:edit school settings'])->group(function () {
                    Route::singleton('/school', SchoolSettingsController::class)
                        ->only('edit', 'update');

                    Route::put('/school/languages', SaveSchoolLanguagesController::class)
                        ->name('school.languages');

                    Route::post('/school/sync/{item}', SyncSchoolItemController::class)
                        ->name('school.item-sync');
                });
            });
    });
});
