<?php

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;

// Docker publishes the app port behind a reverse proxy, so TRUSTED_PROXIES decides whether X-Forwarded-For is believed
beforeEach(function () {
    Route::middleware('web')->get('/client-ip', fn () => request()->ip());
});

afterEach(fn () => TrustProxies::flushState());

it('ignores forwarded headers when no proxies are trusted', function () {
    $this->get('/client-ip', ['X-Forwarded-For' => '203.0.113.7'])->assertSee('127.0.0.1');
});

it('uses forwarded headers when TRUSTED_PROXIES is *', function () {
    TrustProxies::at('*');

    $this->get('/client-ip', ['X-Forwarded-For' => '203.0.113.7'])->assertSee('203.0.113.7');
});
