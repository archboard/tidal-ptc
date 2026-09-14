<?php

beforeEach(function () {
    // Headless Chromium reports UTC, so a user on another timezone gets prompted
    logIn(['timezone' => 'America/Chicago'])->setSchool();
});

it('updates the timezone from the mismatch prompt', function () {
    visit('/')
        ->assertSee('Change your timezone to UTC?')
        ->click('Ok')
        ->waitForEvent('networkidle')
        ->assertDontSee('Change your timezone to UTC?')
        ->assertNoJavaScriptErrors();

    expect($this->user->fresh()->timezone)->toBe('UTC');
});

it('does not prompt when the timezone already matches', function () {
    $this->user->update(['timezone' => 'UTC']);

    visit('/')
        ->assertDontSee('Update your timezone')
        ->assertNoJavaScriptErrors();
});
