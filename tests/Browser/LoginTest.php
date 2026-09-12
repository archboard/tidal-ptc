<?php

beforeEach(function () {
    $this->tenant->update(['allow_password_auth' => true]);
    setSchool();
});

it('logs in with valid credentials', function () {
    $user = seedUser();

    visit('/login')
        ->fill('Email', $user->email)
        ->fill('Password', 'password')
        ->click('Log in')
        ->assertPathIs('/')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

it('shows an inline error for invalid credentials', function () {
    $user = seedUser();

    visit('/login')
        ->fill('Email', $user->email)
        ->fill('Password', 'wrong-password')
        ->click('Log in')
        ->assertPathIs('/login')
        ->assertSee(__('auth.failed'))
        ->assertNoJavaScriptErrors();

    $this->assertGuest();
});
