<?php

beforeEach(function () {
    logIn()->setSchool();

    $this->teacher = seedUser();
    $this->student = seedSection($this->teacher)->students->first();
    // ponytail: headless Chromium reports UTC; a mismatched user timezone opens TimezoneModal over the page
    $this->guardian = tap(seedGuardian($this->student))->update(['timezone' => 'UTC']);
    $this->slot = seedBookableSlot($this->teacher);
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'reserved_at' => now()]);
    $this->actingAs($this->guardian);
});

it('cancels a conference after confirming', function () {
    visit('/')
        ->click('Cancel')
        ->assertSee('Are you sure?')
        ->click('Cancel conference')
        ->waitForEvent('networkidle')
        ->assertSee('Conference cancelled.')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh()->student_id)->toBeNull();
});

it('keeps the conference when the confirmation is dismissed', function () {
    visit('/')
        ->click('Cancel')
        ->assertSee('Are you sure?')
        ->click('Never mind')
        ->assertDontSee('Are you sure?')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh()->student_id)->toBe($this->student->id);
});
