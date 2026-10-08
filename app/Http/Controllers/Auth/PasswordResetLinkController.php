<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as FortifyPasswordResetLinkController;
use Laravel\Fortify\Http\Requests\SendPasswordResetLinkRequest;

/**
 * Scopes the reset link to the selected user type, since users of different types can share an email.
 */
class PasswordResetLinkController extends FortifyPasswordResetLinkController
{
    public function store(SendPasswordResetLinkRequest $request): Responsable
    {
        $request->validate(['user_type' => ['required', Rule::enum(UserType::class)]]);

        $status = $this->broker()->sendResetLink(
            $request->only(Fortify::email(), 'user_type')
        );

        return $status == Password::RESET_LINK_SENT
            ? app(SuccessfulPasswordResetLinkRequestResponse::class, ['status' => $status])
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
    }
}
