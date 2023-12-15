<?php

beforeEach(function () {
    logIn();
});

it('can retrieve timezones', function () {
    $this->get('/timezones')
        ->assertOk()
        ->assertJson(timezones()
            ->map(fn (string $label, string $value) => compact('label', 'value'))
            ->values()
            ->toArray());
});

it('can update the timezone', function () {
    $data = [
        'timezone' => 'America/New_York',
    ];
    $this->put('/settings/timezone', $data)
        ->assertRedirect();

    $this->assertEquals('America/New_York', $this->user->timezone);
});
