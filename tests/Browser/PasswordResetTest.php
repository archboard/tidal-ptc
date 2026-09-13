<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->tenant->update(['allow_password_auth' => true]);
    setSchool();
    $this->user = seedUser();
});

it('requests a password reset link', function () {
    Notification::fake();

    visit('/forgot-password')
        ->fill('Email', $this->user->email)
        ->click('Email Password Reset Link')
        ->waitForEvent('networkidle')
        ->assertSee(__('passwords.sent'))
        ->assertNoJavaScriptErrors();

    Notification::assertSentTo($this->user, ResetPassword::class);
});

it('resets the password from the emailed link', function () {
    $token = Password::broker()->createToken($this->user);

    visit("/reset-password/{$token}?email={$this->user->email}")
        ->fill('Password', 'new-secret-123')
        ->fill('Confirm password', 'new-secret-123')
        ->click('Reset Password')
        ->waitForEvent('networkidle')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();

    expect(Hash::check('new-secret-123', $this->user->fresh()->password))->toBeTrue();
});
