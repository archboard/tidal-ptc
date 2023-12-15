<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it("can't be accessed without permission", function () {
    $this->get(route('settings.school.edit'))
        ->assertForbidden();
});

it('can be accessed with permission', function () {
    fullPermissions()->setSchool();

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('school')
        )
        ->assertOk();
});
