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

it('books a conference from the dashboard modal', function () {
    visit('/')
        ->click('Book')
        ->click('button:has-text("–")')
        ->click('Book conference')
        ->waitForEvent('networkidle')
        ->assertPathIs('/')
        ->assertSee('Conference booked successfully.')
        ->assertDontSee('Book conference')
        ->assertNoJavaScriptErrors();

    expect($this->slot->refresh()->student_id)->toBe($this->student->id);
});

it('books with the override teacher and shows the original in parentheses', function () {
    $override = seedUser(['first_name' => 'Olive', 'last_name' => 'Override']);
    $this->student->sections()->first()->update(['alt_user_id' => $override->id]);
    $slot = seedBookableSlot($override);

    visit('/')
        ->assertSee("{$override->name} ({$this->teacher->name})")
        ->click('Book')
        ->click('button:has-text("–")')
        ->click('Book conference')
        ->waitForEvent('networkidle')
        ->assertSee('Conference booked successfully.')
        ->assertNoJavaScriptErrors();

    expect($slot->refresh()->student_id)->toBe($this->student->id)
        ->and($this->slot->refresh()->student_id)->toBeNull();
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
