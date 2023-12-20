<?php

beforeEach(function () {
    logIn();
});

it('has a personal settings page', function () {
    $this->get(route('settings.personal.edit'))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('settings/Personal')
            ->has('hasPassword')
        );
});

it('can update personal settings', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => fake()->email(),
        'timezone' => fake()->timezone(),
        'is_24h' => fake()->boolean(),
    ];

    $this->put(route('settings.personal.update'), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->user->refresh();
    $this->assertEquals($data['first_name'], $this->user->first_name);
    $this->assertEquals($data['last_name'], $this->user->last_name);
    $this->assertEquals($data['email'], $this->user->email);
    $this->assertEquals($data['timezone'], $this->user->timezone);
    $this->assertEquals($data['is_24h'], $this->user->is_24h);
});
