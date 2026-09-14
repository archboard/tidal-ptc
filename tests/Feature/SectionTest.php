<?php

use App\Enums\Permission;
use App\Models\Section;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();
    $this->section = test()->seedSection();
});

it("can't see section index page without permission", function () {
    $this->get(route('sections.index'))
        ->assertStatus(403);
});

it('can show section index page with permission', function () {
    $this->givePermission(Permission::viewAny, Section::class)
        ->get(route('sections.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sections/Index')
            ->has('title')
            ->has('sections')
        );
});

it("can't show view page without permission", function () {
    $this->get(route('sections.show', $this->section))
        ->assertStatus(403);
});

it('can show view page with permission', function () {
    $this->givePermission(Permission::view, Section::class)
        ->get(route('sections.show', $this->section))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sections/Show')
            ->has('title')
            ->has('section')
        );
});

it('can edit section', function () {
    $this->givePermission(Permission::update, Section::class)
        ->get(route('sections.edit', $this->section))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sections/Edit')
            ->has('title')
            ->has('section')
        );
});
