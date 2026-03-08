<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
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
        );
});

it('reset password link can be requested and reset successfully', function () {
    Notification::fake();

    $user = $this->seedUser();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get('/reset-password/'.$notification->token)
            ->assertOk()
            ->assertViewHas('title')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ResetPassword')
                ->has('title')
                ->has('email')
                ->where('token', $notification->token)
            );

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return true;
    });
});
