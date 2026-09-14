<?php

use Pest\Browser\Api\AwaitableWebpage;

beforeEach(function () {
    // ponytail: headless Chromium reports UTC; a mismatched user timezone opens TimezoneModal over the page
    logIn(['timezone' => 'UTC'])->setSchool();
    fullPermissions();
    $this->slot = seedTimeSlot();
});

// ponytail: FullCalendar re-renders the event on mousedown (drag mirror), so Playwright's hit-target check flags its own
// successful click as intercepted and retries into the open modal until timeout. Click from JS to skip that check.
function openTimeSlotModal(): AwaitableWebpage
{
    $page = visit('/time-slots/create')->assertVisible('.fc-event');
    $page->script('document.querySelector(".fc-event").click()');

    return $page->assertSee('Edit time slot');
}

it('edits a time slot through the calendar modal', function () {
    openTimeSlotModal()
        ->fill('internal:role=dialog >> internal:label="Location"s', 'Room 12')
        ->click('internal:role=dialog >> internal:text="Save"s')
        ->waitForEvent('networkidle')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh()->location)->toBe('Room 12');
});

it('deletes a time slot through the calendar modal', function () {
    $page = openTimeSlotModal()
        ->click('internal:role=dialog >> internal:text="Delete"s')
        ->waitForEvent('networkidle')
        ->assertDontSee('Edit time slot')
        ->assertNoJavaScriptErrors();

    expect($this->slot->fresh())->toBeNull()
        ->and($page->script('document.querySelectorAll(".fc-event").length'))->toBe(0);
});
