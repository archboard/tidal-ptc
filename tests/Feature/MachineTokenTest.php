<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->refreshDatabase();
    $this->asCloud();
});

it('self hosted cant generate token', function () {
    $this->asSelfHosted();

    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist())->toBeTrue();

    $this->artisan('make:token')
        ->assertOk();

    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist())->toBeTrue();
});

it('can new generate token', function () {
    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist())->toBeTrue();

    $this->artisan('make:token')
        ->assertOk();

    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->exists())->toBeTrue();
});

it('can new generate token over existing tokens', function () {
    DB::table('machine_api_tokens')->insert(['api_token' => 'existing']);

    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->exists())->toBeTrue();

    $this->artisan('make:token')
        ->assertOk();

    expect(DB::table('machine_api_tokens')->whereNotNull('api_token')->count())->toBe(1);
    expect(DB::table('machine_api_tokens')->where('api_token', 'existing')->doesntExist())->toBeTrue();
});
