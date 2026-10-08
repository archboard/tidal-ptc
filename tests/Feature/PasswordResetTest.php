<?php

use App\Enums\UserType;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->refreshDatabase();
});

it('tenants without passwords can reset', function () {
    $this->tenant->update(['allow_password_auth' => false]);

    $this->get('/forgot-password')
        ->assertNotFound();
});

it('reset password link screen can be rendered', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertViewHas('title')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/ForgotPassword')
            ->has('title')
            ->has('status')
            ->where('userTypes.1', ['label' => 'Contact/Guardian', 'value' => 'guardian'])
        );
});

it('reset password link can be requested and reset successfully', function () {
    Notification::fake();

    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/forgot-password', ['email' => $user->email, 'user_type' => UserType::staff->value])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)
            ->toBe(route('password.reset', ['token' => $notification->token, 'email' => $user->email, 'user_type' => 'staff']));

        $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email, 'user_type' => 'staff']))
            ->assertOk()
            ->assertViewHas('title')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ResetPassword')
                ->has('title')
                ->where('email', $user->email)
                ->where('userType', 'staff')
                ->where('token', $notification->token)
            );

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'user_type' => UserType::staff->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return true;
    });
});

it('sends and applies the reset for the selected user type when users share an email', function () {
    Notification::fake();
    $staff = $this->seedUser(['user_type' => UserType::staff, 'email' => 'shared@example.com']);
    $guardian = $this->seedUser(['user_type' => UserType::guardian, 'email' => 'shared@example.com']);

    $this->post('/forgot-password', ['email' => 'shared@example.com', 'user_type' => UserType::guardian->value])
        ->assertSessionHasNoErrors();

    Notification::assertNotSentTo($staff, ResetPassword::class);
    Notification::assertSentTo($guardian, ResetPassword::class, function ($notification) use ($staff, $guardian) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => 'shared@example.com',
            'user_type' => UserType::guardian->value,
            'password' => 'new-guardian-password',
            'password_confirmation' => 'new-guardian-password',
        ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('new-guardian-password', $guardian->fresh()->password))->toBeTrue()
            ->and(Hash::check('password', $staff->fresh()->password))->toBeTrue();

        return true;
    });
});

it('requires a valid user type to request a reset link', function (?string $userType, string $message) {
    Notification::fake();
    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/forgot-password', ['email' => $user->email, 'user_type' => $userType])
        ->assertSessionHasErrors(['user_type' => $message]);

    Notification::assertNothingSent();
})->with([
    'missing' => [null, 'This field is required.'],
    'unknown' => ['admin', 'The selected user type is invalid.'],
]);

it('requires a valid user type to reset a password', function (?string $userType, string $message) {
    $user = $this->seedUser(['user_type' => UserType::staff]);
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'user_type' => $userType,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertSessionHasErrors(['user_type' => $message]);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with([
    'missing' => [null, 'This field is required.'],
    'unknown' => ['admin', 'The selected user type is invalid.'],
]);
