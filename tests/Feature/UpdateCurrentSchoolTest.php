<?php

use App\Enums\Role;
use App\Enums\UserType;
use App\Models\Student;

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

it('lets a guardian switch to any school where they have a linked student', function () {
    $this->user->update(['user_type' => UserType::guardian]);
    $other = $this->tenant->schools()->where('id', '!=', $this->school->id)->where('active', true)->firstOrFail();
    $this->user->students()->attach(Student::factory()->create(['tenant_id' => $this->tenant->id, 'school_id' => $other->id]));

    expect($this->user->adminSchools()->pluck('id')->all())->toBe([$other->id]);

    $this->put(route('settings.current-school.update'), ['school_id' => $other->id])
        ->assertSessionDoesntHaveErrors();

    expect($this->user->fresh()->school_id)->toBe($other->id);
});
