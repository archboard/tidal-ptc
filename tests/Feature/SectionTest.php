<?php

use App\Models\Section;

beforeEach(function () {
    logIn()->setSchool();
    $this->section = test()->seedSection();
});

it("can't see section index page without permission", function () {
    $this->get(route('sections.index'))
        ->assertStatus(403);
});

it('can show section index page with permission', function () {
    $this->givePermission(\App\Enums\Permission::viewAny, Section::class)
        ->get(route('sections.index'))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
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
    $this->givePermission(\App\Enums\Permission::view, Section::class)
        ->get(route('sections.show', $this->section))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('sections/Show')
            ->has('title')
            ->has('section')
        );
});
