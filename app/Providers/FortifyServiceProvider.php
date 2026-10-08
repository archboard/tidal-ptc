<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Enums\UserType;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\NewPasswordController as FortifyNewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as FortifyPasswordResetLinkController;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FortifyPasswordResetLinkController::class, PasswordResetLinkController::class);
        $this->app->bind(FortifyNewPasswordController::class, NewPasswordController::class);
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

        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
            'user_type' => $user->user_type?->value,
        ]));

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::authenticateUsing(function (Request $request) {
            $request->validate(['user_type' => ['required', Rule::enum(UserType::class)]]);

            $user = User::where(DB::raw('lower(email)'), strtolower($request->input('email', '')))
                ->where('user_type', $request->input('user_type'))
                ->first();

            if ($user && $user->password && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }
        });

        Fortify::loginView(function () {
            $title = __('Log in');

            return inertia('Auth/Login', [
                'title' => $title,
                'tenant' => Tenant::current()?->only(['allow_oidc_login', 'allow_password_auth']) ?? new \stdClass,
                'status' => session('status'),
                'userTypes' => UserType::selectOptions(),
            ])->withViewData(compact('title'));
        });

        Fortify::requestPasswordResetLinkView(function () {
            $title = __('Forgot password');

            return inertia('Auth/ForgotPassword', [
                'title' => $title,
                'status' => session('status'),
                'userTypes' => UserType::selectOptions(),
            ])->withViewData(compact('title'));
        });

        Fortify::resetPasswordView(function (Request $request) {
            $title = __('Create a new password');

            return inertia('Auth/ResetPassword', [
                'title' => $title,
                'email' => $request->email,
                'userType' => $request->user_type,
                'token' => $request->route('token'),
            ])->withViewData(compact('title'));
        });

        Fortify::confirmPasswordView(function () {
            throw new \Exception('Not implemented');
        });
    }
}
