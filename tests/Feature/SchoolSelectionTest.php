<?php

use App\Exceptions\SisNotConfiguredException;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it('will redirect when no school is set', function () {
});

it('will throw an exception without schools', function () {
    $this->tenant->schools()->delete();

    $this->withoutExceptionHandling();
    $this->expectException(SisNotConfiguredException::class);
    $this->get(route('select-school'));
});

it('can view the school selection page', function () {
    $this->get(route('select-school'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SchoolSelection')
            ->has('schools')
            ->has('title')
            ->where('endpoint', route('select-school'))
        );
});

it("'can update a user's school'", function () {
    $school = $this->tenant->schools->random();

    $this->post(route('select-school'), [
        'school_id' => $school->id,
    ])
        ->assertSessionHas('success')
        ->assertRedirect(route('home'));

    $this->user->refresh();
    expect($this->user->school_id)->toBe($school->id);
});
