<?php

use App\Providers\RouteServiceProvider;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->refreshDatabase();
});

it('tenant without passwords cant login', function () {
    $user = $this->seedUser();
    $this->tenant->update(['allow_password_auth' => false]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertNotFound();
});

it('login screen can be rendered', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewHas('title')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
            ->has('title')
            ->has('status')
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
        );
});

it('users can authenticate using the login screen', function () {
    $user = $this->seedUser();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(RouteServiceProvider::HOME);

    $this->assertAuthenticatedAs($user);
});

it('users can not authenticate with invalid password', function () {
    $user = $this->seedUser();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

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
