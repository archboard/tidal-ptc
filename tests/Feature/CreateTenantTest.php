<?php

use App\Console\Commands\CreateTenant;
use App\Models\Tenant;

beforeEach(function () {
    $this->asCloud();
    $this->arguments = [
        'name' => 'Test District',
        'domain' => 'test-district.example.com',
        'license' => '5f0c6a8e-3c1b-4a55-9d0e-2b7f1c9a4e11',
        'schools' => '3',
    ];
});

it('creates a tenant and prints its setup links', function () {
    $this->artisan(CreateTenant::class, $this->arguments)
        ->expectsOutputToContain('https://test-district.example.com/setup/')
        ->assertSuccessful();

    expect(Tenant::firstWhere('domain', 'test-district.example.com'))
        ->name->toBe('Test District')
        ->license->toBe('5f0c6a8e-3c1b-4a55-9d0e-2b7f1c9a4e11')
        ->school_limit->toBe(3)
        ->subscription_expires_at->not->toBeNull();
});

it('rejects a domain or license that is already taken', function (string $argument) {
    $this->artisan(CreateTenant::class, [...$this->arguments, $argument => $this->tenant->{$argument}])
        ->assertFailed();

    expect(Tenant::count())->toBe(1);
})->with(['domain', 'license']);

it('refuses to run when self-hosted', function () {
    $this->asSelfHosted();

    $this->artisan(CreateTenant::class, $this->arguments)
        ->assertFailed();

    expect(Tenant::count())->toBe(1);
});
