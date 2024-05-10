<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();

    $this->school->update(['timezone' => 'Asia/Shanghai']);
});

it('has time slots page', function () {
    $this->get(route('time-slots.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/Index')
            ->has('title')
            ->has('eventSources')
        );
});
