<?php

use App\Models\Tenant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->asCloud();
    config([
        'services.auth_broker.url' => 'https://broker.test',
        'services.auth_broker.machine_token' => 'machine-secret',
    ]);
    Http::fake();
});

function sentToBroker(string $method, string $path, array $body): bool
{
    return Http::recorded()->contains(fn (array $pair) => $pair[0]->method() === $method
        && $pair[0]->url() === "https://broker.test/api/apps/tidal-ptc/$path"
        && $pair[0]->hasHeader('Authorization', 'Bearer machine-secret')
        && $pair[0]->data() === $body);
}

it('registers the new origin and removes the old one when the domain changes', function () {
    $tenant = Tenant::factory()->create(['domain' => 'old.example.com', 'custom_domain' => null]);
    Http::fake();

    $tenant->update(['domain' => 'new.example.com']);

    expect(sentToBroker('PUT', "licenses/{$tenant->license}", ['kind' => 'cloud']))->toBeTrue()
        ->and(sentToBroker('PUT', 'origins', ['license' => $tenant->license, 'origin' => 'https://new.example.com']))->toBeTrue()
        ->and(sentToBroker('DELETE', 'origins', ['origin' => 'https://old.example.com']))->toBeTrue();
});

it('removes the origins when the tenant is deleted', function () {
    $tenant = Tenant::factory()->create(['domain' => 'gone.example.com', 'custom_domain' => 'custom.example.com']);
    Http::fake();

    $tenant->delete();

    expect(sentToBroker('DELETE', 'origins', ['origin' => 'https://gone.example.com']))->toBeTrue()
        ->and(sentToBroker('DELETE', 'origins', ['origin' => 'https://custom.example.com']))->toBeTrue();
    Http::assertNotSent(fn (Request $request) => $request->method() === 'PUT');
});

it('sends nothing when the install is not cloud', function () {
    config(['app.cloud' => false]);

    Tenant::factory()->create()->update(['domain' => 'new.example.com']);

    Http::assertNothingSent();
});
