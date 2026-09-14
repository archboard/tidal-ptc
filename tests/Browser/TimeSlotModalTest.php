<?php

beforeEach(function () {
    // ponytail: headless Chromium reports UTC; a mismatched user timezone opens TimezoneModal over the page
    logIn(['timezone' => 'UTC'])->setSchool();
    fullPermissions();
    $this->slot = seedTimeSlot();
});

it('edits a time slot through the calendar modal', function () {
    visit('/time-slots/create')
        ->click('.fc-event')
        ->assertSee('Edit time slot')
        ->fill('internal:role=dialog >> internal:label="Location"s', 'Room 12')
        ->click('internal:role=dialog >> internal:text="Save"s')
        ->waitForEvent('networkidle')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh()->location)->toBe('Room 12');
});

it('deletes a time slot through the calendar modal', function () {
    $page = visit('/time-slots/create')
        ->click('.fc-event')
        ->assertSee('Edit time slot')
        ->click('internal:role=dialog >> internal:text="Delete"s')
        ->waitForEvent('networkidle')
        ->assertDontSee('Edit time slot')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh())->toBeNull()
        ->and($page->script('document.querySelectorAll(".fc-event").length'))->toBe(0);
});
