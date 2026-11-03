<?php

beforeEach(function () {
    logIn();
});

it('can retrieve timezones', function () {
    $this->get('/timezones')
        ->assertOk()
        ->assertJson(timezones()->toArray());
});

it('can update the timezone', function () {
    $data = [
        'timezone' => 'America/New_York',
    ];
    $this->put('/settings/timezone', $data)
        ->assertRedirect();

    $this->assertEquals('America/New_York', $this->user->timezone);
});

it('sets the current school timezone when it is empty', function () {
    setSchool();
    $this->school->update(['timezone' => null]);

    $this->put('/settings/timezone', ['timezone' => 'America/New_York'])
        ->assertRedirect();

    expect($this->school->fresh()->timezone)->toBe('America/New_York');
});

it('keeps the current school timezone when it is already set', function () {
    setSchool();
    $this->school->update(['timezone' => 'America/Chicago']);

    $this->put('/settings/timezone', ['timezone' => 'America/New_York'])
        ->assertRedirect();

    expect($this->school->fresh()->timezone)->toBe('America/Chicago');
});
