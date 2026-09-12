<?php

it('selects a school and shows the flash notification', function () {
    logIn();
    $school = $this->tenant->schools->first();

    visit('/select-school')
        ->click(sprintf('internal:text="%s"s', $school->name))
        ->click('Save')
        ->waitForEvent('networkidle')
        ->assertPathIs('/')
        ->assertSee('School selected successfully')
        ->assertNoJavaScriptErrors();

    expect($this->user->fresh()->school_id)->toBe($school->id);
});
