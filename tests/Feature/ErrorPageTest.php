<?php

use Inertia\Testing\AssertableInertia;

it('renders the branded error page outside local', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Error')
            ->where('status', 404)
        );
});

it('leaves the default error response in local', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertDontSee('"component":"Error"', false);
});
