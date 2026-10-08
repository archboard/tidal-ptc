<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Actions\CompletePasswordReset;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\NewPasswordController as FortifyNewPasswordController;

/**
 * Resets the password of the user with the type from the reset link, since users of different types can share an email.
 */
class NewPasswordController extends FortifyNewPasswordController
{
    public function store(Request $request): Responsable
    {
        $request->validate([
            'token' => 'required',
            Fortify::email() => 'required|email',
            'user_type' => ['required', Rule::enum(UserType::class)],
            'password' => 'required',
        ]);

        $status = $this->broker()->reset(
            $request->only(Fortify::email(), 'user_type', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                app(ResetsUserPasswords::class)->reset($user, $request->all());

                app(CompletePasswordReset::class)($this->guard, $user);
            }
        );

        return $status == Password::PASSWORD_RESET
            ? app(PasswordResetResponse::class, ['status' => $status])
            : app(FailedPasswordResetResponse::class, ['status' => $status]);
    }
}
