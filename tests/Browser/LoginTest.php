<?php

use App\Enums\UserType;

beforeEach(function () {
    $this->tenant->update(['allow_password_auth' => true]);
    setSchool();
});

it('logs in with valid credentials', function () {
    $user = seedUser(['user_type' => UserType::staff]);

    visit('/login')
        ->fill('Email', $user->email)
        ->fill('Password', 'password')
        ->click('Log in')
        ->waitForEvent('networkidle')
        ->assertPathIs('/')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

it('logs in as the selected user type', function () {
    seedUser(['user_type' => UserType::staff, 'email' => 'shared@example.com']);
    $guardian = seedUser(['user_type' => UserType::guardian, 'email' => 'shared@example.com', 'password' => bcrypt('guardian-password')]);

    visit('/login')
        ->click('Contact/Guardian')
        ->fill('Email', 'shared@example.com')
        ->fill('Password', 'guardian-password')
        ->click('Log in')
        ->waitForEvent('networkidle')
        ->assertPathIs('/')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($guardian);
});

it('shows an inline error for invalid credentials', function () {
    $user = seedUser(['user_type' => UserType::staff]);

    visit('/login')
        ->fill('Email', $user->email)
        ->fill('Password', 'wrong-password')
        ->click('Log in')
        ->waitForEvent('networkidle')
        ->assertPathIs('/login')
        ->assertSee(__('auth.failed'))
        ->assertNoJavaScriptErrors();

    $this->assertGuest();
});
