<?php

use App\Console\Commands\CreateTenant;
use App\Models\Tenant;

beforeEach(function () {
    $this->asCloud();
});

it('creates a tenant and prints its setup links', function () {
    $this->artisan(CreateTenant::class, ['name' => 'Test District', 'domain' => 'test-district.example.com'])
        ->expectsOutputToContain('https://test-district.example.com/setup/')
        ->assertSuccessful();

    expect(Tenant::firstWhere('domain', 'test-district.example.com'))
        ->name->toBe('Test District')
        ->license->not->toBeNull()
        ->subscription_expires_at->not->toBeNull();
});

it('rejects a domain that is already taken', function () {
    $this->artisan(CreateTenant::class, ['name' => 'Duplicate', 'domain' => $this->tenant->domain])
        ->assertFailed();

    expect(Tenant::count())->toBe(1);
});

it('refuses to run when self-hosted', function () {
    $this->asSelfHosted();

    $this->artisan(CreateTenant::class, ['name' => 'Test District', 'domain' => 'test-district.example.com'])
        ->assertFailed();

    expect(Tenant::count())->toBe(1);
});
