<?php

use Inertia\Testing\AssertableInertia;

it('renders the branded error page when debug is off', function () {
    config(['app.debug' => false]);

    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Error')
            ->where('status', 404)
        );
});

it('leaves the default error response when debug is on', function () {
    config(['app.debug' => true]);

    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertDontSee('"component":"Error"', false);
});
