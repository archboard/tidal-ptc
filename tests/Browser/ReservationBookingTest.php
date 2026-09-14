<?php

beforeEach(function () {
    logIn()->setSchool();
    $this->school->update(['allow_online_meetings' => true]);

    $this->teacher = seedUser();
    $this->student = seedSection($this->teacher)->students->first();
    // ponytail: headless Chromium reports UTC; a mismatched user timezone opens TimezoneModal over the page
    $this->guardian = tap(seedGuardian($this->student))->update(['timezone' => 'UTC']);
    $this->slot = seedBookableSlot($this->teacher);
    $this->actingAs($this->guardian);
});

it('books a conference from the booking page', function () {
    visit("/reservations/create/{$this->student->id}/{$this->teacher->id}")
        ->click('button:has-text("–")')
        ->fill('Notes', 'Talk about reading')
        ->click('Book conference')
        ->waitForEvent('networkidle')
        ->assertPathIs('/')
        ->assertSee('Conference booked successfully.')
        ->assertNoJavaScriptErrors();

    $this->slot->refresh();
    expect($this->slot->student_id)->toBe($this->student->id)
        ->and($this->slot->reserved_by)->toBe($this->guardian->id)
        ->and($this->slot->contact_notes)->toBe('Talk about reading');
});

it('hides the booking form when a selected slot is cancelled', function () {
    visit("/reservations/create/{$this->student->id}/{$this->teacher->id}")
        ->click('button:has-text("–")')
        ->assertSee('Book conference')
        ->click('Cancel')
        ->assertDontSee('Book conference')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh()->student_id)->toBeNull();
});
