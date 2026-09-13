<?php

use App\Enums\Role;

beforeEach(function () {
    logIn();
    setSchool();
});

it('lets a district admin switch to any active tenant school', function () {
    $this->user->assign(Role::DISTRICT_ADMIN->value);
    $other = $this->tenant->schools()->where('id', '!=', $this->school->id)->where('active', true)->firstOrFail();

    $this->put(route('settings.current-school.update'), ['school_id' => $other->id])
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    expect($this->user->fresh()->school_id)->toBe($other->id);
});

it('rejects a school the user is not assigned to', function () {
    $other = $this->tenant->schools()->where('id', '!=', $this->school->id)->where('active', true)->firstOrFail();

    $this->put(route('settings.current-school.update'), ['school_id' => $other->id])
        ->assertSessionHasErrors('school_id');
});
