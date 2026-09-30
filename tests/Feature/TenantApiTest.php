<?php

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->refreshDatabase();
    $this->asCloud();

    $token = Str::random();

    DB::table('machine_api_tokens')
        ->insert(['api_token' => hash('sha256', $token)]);

    $this->headers = [
        'Authorization' => "Bearer {$token}",
    ];
});

it('cant access cloud endpoints', function () {
    $this->asSelfHosted()
        ->getJson('/api/tenants')
        ->assertNotFound();
});

it('need machine token guard', function () {
    $this->getJson('/api/tenants')
        ->assertUnauthorized();
});

it('can get tenants', function () {
    Tenant::factory()->count(3)->create();

    $json = $this->getJson('/api/tenants', $this->headers)
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links'])
        ->json();

    expect($json['data'])->toHaveCount(4);
});

it('can create tenant', function () {
    Queue::fake();

    $data = [
        'name' => fake()->company(),
        'domain' => fake()->domainName(),
        'license' => fake()->uuid(),
        'subscription_started_at' => now()->subMonth()->toDateTimeString(),
        'subscription_expires_at' => now()->addYear()->toDateTimeString(),
    ];

    $this->postJson('/api/tenants', $data, $this->headers)
        ->assertCreated();

    expect(Tenant::query()->where('license', $data['license'])->exists())->toBeTrue();
});

it('returns signed setup links on the new tenant\'s domain', function (string $link) {
    $response = $this->postJson('/api/tenants', [
        'name' => fake()->company(),
        'domain' => 'new-district.example.com',
        'license' => fake()->uuid(),
        'subscription_started_at' => now()->subMonth()->toDateTimeString(),
        'subscription_expires_at' => now()->addYear()->toDateTimeString(),
    ], $this->headers)->assertCreated();

    $url = $response->json($link);
    expect($url)->toStartWith('https://new-district.example.com/setup/')
        ->and(URL::hasValidRelativeSignature(Request::create($url)))->toBeTrue();
})->with(['setup_url', 'plugin_url']);
