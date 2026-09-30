<?php

use App\Console\Commands\IssueTenantSetupLinks;
use App\Enums\Role;
use App\Models\Tenant;

beforeEach(function () {
    $this->asCloud();
    $this->other = Tenant::factory()->create(['domain' => 'other.example.com']);
    Tenant::forgetCurrent();
});

it('prints setup links for a tenant found by id or domain', function (string $key) {
    $this->artisan(IssueTenantSetupLinks::class, ['tenant' => (string) $this->other->{$key}])
        ->expectsOutputToContain('https://other.example.com/setup/')
        ->assertSuccessful();
})->with(['id', 'domain']);

it('refuses once the tenant has a district admin', function () {
    $this->other->execute(fn () => seedUser(['tenant_id' => $this->other->id])->assignRole(Role::DISTRICT_ADMIN));

    $this->artisan(IssueTenantSetupLinks::class, ['tenant' => 'other.example.com'])
        ->doesntExpectOutputToContain('/setup/')
        ->assertFailed();
});
