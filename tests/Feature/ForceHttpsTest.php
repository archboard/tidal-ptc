<?php

use App\Providers\AppServiceProvider;

// Octane workers behind a TLS-terminating proxy see plain http, so production must force https links
it('generates https urls in production', function () {
    $this->app->detectEnvironment(fn () => 'production');
    (new AppServiceProvider($this->app))->boot();

    expect(url('/login'))->toStartWith('https://');
});

it('keeps the request scheme outside production', function () {
    $this->get('http://localhost/up');

    expect(url('/login'))->toStartWith('http://');
});
