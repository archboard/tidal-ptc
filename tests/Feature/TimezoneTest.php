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
