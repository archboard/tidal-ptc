<?php

use App\Enums\UserType;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->asCloud();
    config(['services.billing.url' => 'https://billing.example.com']);
    // The tenant has two active schools
    $this->tenant->update(['school_limit' => 1]);
});

it('blocks staff with a 402 when the district is over its school limit', function () {
    logIn(['user_type' => UserType::staff]);
    setSchool();

    $this->get(route('home'))
        ->assertPaymentRequired()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Error')
            ->where('status', 402)
            ->where('links', [])
        );
});

it('shows district admins how to fix it', function () {
    logIn(['user_type' => UserType::staff]);
    setSchool();
    fullPermissions();

    $this->get(route('home'))
        ->assertPaymentRequired()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('links.0.href', route('settings.tenant.edit'))
            ->where('links.1.href', 'https://billing.example.com/quotes/create?product=tidal_ptc&schools=2')
        );
});

it('returns a JSON 402 to API calls from the page', function () {
    logIn(['user_type' => UserType::staff]);
    setSchool();

    $this->getJson(route('teachers.index'))->assertPaymentRequired();
});

it('keeps settings reachable while over the limit', function () {
    logIn(['user_type' => UserType::staff]);
    setSchool();
    fullPermissions();

    $this->get(route('settings.tenant.edit'))->assertOk();
});

it('lets guardians through while over the limit', function () {
    logIn(['user_type' => UserType::guardian]);
    setSchool();

    $this->get(route('home'))->assertOk();
});

it('lets staff through when the district is within its limit', function (?int $limit, bool $cloud) {
    $this->tenant->update(['school_limit' => $limit]);
    $cloud ? $this->asCloud() : $this->asSelfHosted();
    logIn(['user_type' => UserType::staff]);
    setSchool();

    $this->get(route('home'))->assertOk();
})->with([
    'at the limit' => [2, true],
    'no limit' => [null, true],
    'self-hosted over the limit' => [1, false],
]);
