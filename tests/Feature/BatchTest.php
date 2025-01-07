<?php

use App\Enums\Permission;
use App\Models\TimeSlot;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();
});

it('has batch page', function () {
    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('batches.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/batches/Index')
            ->has('title')
            ->has('batches')
            ->has('breadcrumbs')
        );
});
