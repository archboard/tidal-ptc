<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where(DB::raw('lower(email)'), strtolower($request->input('email', '')))
                ->first();

            if ($user && Hash::check($request->input('password'), $user->password)) {
                return $user;
            }
        });

        Fortify::loginView(function () {
            $title = __('Log in');

            return inertia('Auth/Login', [
                'title' => $title,
                'status' => session('status'),
            ])->withViewData(compact('title'));
        });

        Fortify::requestPasswordResetLinkView(function () {
            $title = __('Forgot password');

            return inertia('Auth/ForgotPassword', [
                'title' => $title,
                'status' => session('status'),
            ])->withViewData(compact('title'));
        });

        Fortify::resetPasswordView(function (Request $request) {
            $title = __('Create a new password');

            return inertia('Auth/ResetPassword', [
                'title' => $title,
                'email' => $request->email,
                'token' => $request->route('token'),
            ])->withViewData(compact('title'));
        });

        Fortify::confirmPasswordView(function () {
            throw new \Exception('Not implemented');
        });
    }
}
