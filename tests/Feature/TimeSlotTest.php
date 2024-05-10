<?php

use App\Enums\Permission;
use App\Models\TimeSlot;
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
            ->has('breadcrumbs')
        );
});

it('can check creation authorization', function () {
    $this->get(route('time-slots.create'))
        ->assertForbidden();
});

it('can view the create time slot page', function () {
    $this->givePermission(Permission::create, TimeSlot::class)
        ->get(route('time-slots.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/Create')
            ->has('title')
            ->has('events')
            ->has('breadcrumbs')
        );
});
