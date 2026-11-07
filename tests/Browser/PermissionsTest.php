<?php

use Pest\Browser\Execution;
use Silber\Bouncer\BouncerFacade as Bouncer;

beforeEach(function () {
    // ponytail: headless Chromium reports UTC; a mismatched user timezone opens TimezoneModal over the page
    logIn(['timezone' => 'UTC'])->setSchool();
    fullPermissions();
    $this->subject = seedUser();
});

it('grants all school permissions and reloads the permission state', function () {
    $page = visit("/users/{$this->subject->id}/permissions")
        ->assertNotChecked('internal:label="Full access"s >> nth=0')
        ->click('Grant all permissions for this school')
        ->waitForEvent('networkidle')
        // ponytail: the PUT triggers a second router.reload request; networkidle only covers the first
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect(Bouncer::scope()->onceTo($this->school->id, fn () => $this->subject->can('do anything')))->toBeTrue();
    // Every per-model "Full access" box flips on once the partial reload lands
    expect($page->script('[...document.querySelectorAll("label")].filter(l => l.textContent.trim() === "Full access").every(l => l.querySelector("input").checked)'))->toBeTrue();
});

it('revokes a single school permission', function () {
    Bouncer::scope()->onceTo($this->school->id, fn () => Bouncer::allow($this->subject)->to('edit permissions'));

    $page = visit("/users/{$this->subject->id}/permissions")
        ->click('Edit user permissions');

    // ponytail: the checkbox flips before the PUT and nothing visible marks its end, so retry the check; each retry lets the in-process server run
    Execution::instance()->waitForExpectation(
        fn () => expect(Bouncer::scope()->onceTo($this->school->id, fn () => $this->subject->can('edit permissions')))->toBeFalse(),
    );

    $page->assertNoJavaScriptErrors();
});
