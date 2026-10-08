<?php

use App\Enums\UserType;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->refreshDatabase();
});

it('tenant without passwords cant login', function () {
    $user = $this->seedUser(['user_type' => UserType::staff]);
    $this->tenant->update(['allow_password_auth' => false]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'user_type' => UserType::staff->value,
    ])->assertNotFound();
});

it('login screen can be rendered', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewHas('title')
        ->assertSee('<link rel="icon" type="image/png" href="/favicon.png" />', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
            ->has('title')
            ->has('status')
            ->has('tenant.allow_password_auth')
            ->where('userTypes.1', ['label' => 'Contact/Guardian', 'value' => 'guardian'])
        );
});

it('login screen can be rendered when passwords are disabled', function () {
    $this->tenant->update(['allow_password_auth' => false]);

    $this->get('/login')
        ->assertOk()
        ->assertViewHas('title')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
            ->has('title')
            ->has('status')
            ->where('tenant.allow_password_auth', false)
        );
});

it('users can authenticate using the login screen', function () {
    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'user_type' => UserType::staff->value,
    ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('users can not authenticate with invalid password', function () {
    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'user_type' => UserType::staff->value,
    ]);

    $this->assertGuest();
});

it('logs in as the selected user type when users share an email', function (UserType $userType) {
    $staff = $this->seedUser(['user_type' => UserType::staff, 'email' => 'shared@example.com', 'password' => bcrypt('staff-password')]);
    $guardian = $this->seedUser(['user_type' => UserType::guardian, 'email' => 'shared@example.com', 'password' => bcrypt('guardian-password')]);
    $user = $userType === UserType::staff ? $staff : $guardian;

    $this->post('/login', [
        'email' => 'Shared@example.com',
        'password' => "{$userType->value}-password",
        'user_type' => $userType->value,
    ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
})->with([
    'staff' => UserType::staff,
    'guardian' => UserType::guardian,
]);

it('users can not authenticate as a different user type', function () {
    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'user_type' => UserType::guardian->value,
    ])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

it('requires a valid user type to log in', function (?string $userType, string $message) {
    $user = $this->seedUser(['user_type' => UserType::staff]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'user_type' => $userType,
    ])
        ->assertSessionHasErrors(['user_type' => $message]);

    $this->assertGuest();
})->with([
    'missing' => [null, 'This field is required.'],
    'unknown' => ['admin', 'The selected user type is invalid.'],
]);

it('users can logout', function () {
    $user = $this->seedUser();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('users can logout when password auth is disabled', function () {
    $user = $this->seedUser();
    $this->tenant->update(['allow_password_auth' => false]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('guest cannot logout', function () {
    $this->post('/logout')
        ->assertRedirect('/login');
});

it('redirects guests to the login page', function () {
    $this->get(route('time-slots.index'))
        ->assertRedirect(route('login'));
});
