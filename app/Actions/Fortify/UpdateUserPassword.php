<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  mixed  $user
     * @return void
     */
    public function update($user, array $input)
    {
        Validator::make($input, [
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
            'password' => $this->passwordRules(),
        ])->after(function ($validator) use ($user, $input) {
            if (
                $user->password &&
                (
                    ! isset($input['current_password']) ||
                    ! Hash::check($input['current_password'], $user->password)
                )
            ) {
                $validator->errors()->add('current_password', __('The provided password does not match your current password.'));
            }
        })->validateWithBag('default');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        session()->flash('success', __('Password updated successfully.'));
    }
}
