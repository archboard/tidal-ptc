<?php

use App\Models\Tenant;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->asCloud();
});

it('sends visitors without a district to the lookup page', function () {
    Tenant::forgetCurrent();

    $this->get('/login')->assertRedirect(route('district.create'));
    $this->get('/district')
        ->assertInertia(fn (AssertableInertia $page) => $page->component('DistrictLookup'));
});

it('redirects a matching code to the district login page', function () {
    $tenant = Tenant::factory()->create(['domain' => 'other.example.com', 'code' => 'AB12']);

    $this->post('/district', ['code' => ' ab12 '])
        ->assertRedirect("https://{$tenant->domain}/login");
});

it('rejects a code that matches no district', function () {
    $this->post('/district', ['code' => 'ZZZZ'])
        ->assertSessionHasErrors(['code' => __('No district was found with that code.')]);
});

it('rejects a malformed code', function (string $code) {
    $this->post('/district', ['code' => $code])->assertSessionHasErrors('code');
})->with(['', 'ABC', 'ABCDE', 'AB-1']);

it('rejects lookups after ten attempts in a minute', function () {
    Tenant::factory()->create(['domain' => 'other.example.com', 'code' => 'AB12']);

    foreach (range(1, 10) as $attempt) {
        $this->post('/district', ['code' => 'ZZZZ']);
    }

    $this->post('/district', ['code' => 'AB12'])
        ->assertSessionHasErrors(['code' => __('Too many attempts. Please try again in a minute.')]);
});

it('returns 404 for the lookup page when self-hosted', function () {
    $this->asSelfHosted();

    $this->get('/district')->assertNotFound();
});

it('shares the district code with signed in users only in the cloud', function (bool $cloud) {
    $cloud ? $this->asCloud() : $this->asSelfHosted();

    $this->logIn()
        ->get(route('settings.personal.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $cloud
            ? $page->where('tenant.code', $this->tenant->code)
            : $page->missing('tenant.code'));
})->with(['cloud' => true, 'self-hosted' => false]);

it('gives each new tenant a unique four character code', function () {
    $codes = Tenant::factory(5)->create()->pluck('code');

    expect($codes)->each->toMatch('/^[A-Z0-9]{4}$/')
        ->and($codes->unique())->toHaveCount(5);
});
