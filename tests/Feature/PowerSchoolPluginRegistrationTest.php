<?php

use App\Events\PowerSchoolPluginRegistered;
use App\Models\Tenant;
use App\Services\PowerSchoolPluginService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

function registrationPayload(string $host, array $overrides = []): array
{
    return [
        'verify_url' => 'https://ps.example.com/ws/v1/time',
        'credentials' => [
            'client_id' => 'new-client-id',
            'client_secret' => 'new-client-secret',
        ],
        'callback_data' => PowerSchoolPluginService::registrationKey($host),
        ...$overrides,
    ];
}

function fakePowerSchool(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://ps.example.com/ws/v1/time' => Http::response(['time' => '2026-10-03T12:00:00.000Z']),
    ]);
}

it('stores verified credentials on the existing tenant', function () {
    Event::fake([PowerSchoolPluginRegistered::class]);
    fakePowerSchool();
    $this->freezeTime();

    $this->postJson(route('powerschool.registration'), registrationPayload($this->tenant->domain))
        ->assertOk()
        ->assertExactJson([
            'callback_result' => '200',
            'message' => 'SUCCESS',
            'time' => now('UTC')->format('Y-m-d\TH:i:s.v\Z'),
        ]);

    expect($this->tenant->fresh()->sis_config->only(['url', 'client_id', 'client_secret'])->all())->toBe([
        'url' => 'https://ps.example.com',
        'client_id' => 'new-client-id',
        'client_secret' => 'new-client-secret',
    ])->and(Tenant::count())->toBe(1);

    Event::assertNotDispatched(PowerSchoolPluginRegistered::class);
});

it('starts a self-hosted tenant and fills its install form', function () {
    Event::fake([PowerSchoolPluginRegistered::class]);
    fakePowerSchool();

    $this->postJson('https://new.example.com/api/powerschool/registration', registrationPayload('new.example.com'))
        ->assertOk()
        ->assertJson(['callback_result' => '200']);

    $tenant = Tenant::getByHost('new.example.com');

    expect($tenant->name)->toBe('new.example.com')
        ->and($tenant->sis_config->get('client_id'))->toBe('new-client-id');

    Event::assertDispatched(PowerSchoolPluginRegistered::class, fn ($event) => $event->sisConfig === [
        'url' => 'https://ps.example.com',
        'client_id' => 'new-client-id',
        'client_secret' => 'new-client-secret',
    ]);
});

it('fills the install form on reload without websockets', function () {
    Event::fake([PowerSchoolPluginRegistered::class]);
    fakePowerSchool();

    $this->postJson('https://new.example.com/api/powerschool/registration', registrationPayload('new.example.com'));

    $this->get('https://new.example.com/install')
        ->assertInertia(fn ($page) => $page->where('sisConfig', [
            'url' => 'https://ps.example.com',
            'client_id' => 'new-client-id',
            'client_secret' => 'new-client-secret',
        ]));
});

it("doesn't create a tenant in the cloud", function () {
    $this->asCloud();
    fakePowerSchool();

    $this->postJson('https://new.example.com/api/powerschool/registration', registrationPayload('new.example.com'))
        ->assertOk()
        ->assertJson(['callback_result' => '500', 'message' => 'Unknown district']);

    expect(Tenant::getByHost('new.example.com'))->toBeNull();
});

it('rejects a key made for another host', function () {
    Http::preventStrayRequests();

    $this->postJson(route('powerschool.registration'), registrationPayload('other.example.com'))
        ->assertOk()
        ->assertJson(['callback_result' => '500', 'message' => 'Invalid registration key']);

    expect($this->tenant->fresh()->sis_config->get('client_id'))->not->toBe('new-client-id');
});

it("doesn't store credentials when PowerSchool's time resource fails", function () {
    Http::preventStrayRequests();
    Http::fake(['https://ps.example.com/ws/v1/time' => Http::response(status: 500)]);

    $this->postJson(route('powerschool.registration'), registrationPayload($this->tenant->domain))
        ->assertOk()
        ->assertJson(['callback_result' => '500']);

    expect($this->tenant->fresh()->sis_config->get('client_id'))->not->toBe('new-client-id');
});

it('reports an unreachable host', function () {
    Http::preventStrayRequests();
    Http::fake(['https://ps.example.com/*' => Http::failedConnection('cURL error 6: Could not resolve host')]);

    $this->postJson(route('powerschool.registration'), registrationPayload($this->tenant->domain))
        ->assertOk()
        ->assertJson(['callback_result' => '500', 'message' => 'HOST_UNKNOWN']);
});

it('requires the registration fields', function () {
    $this->postJson(route('powerschool.registration'))
        ->assertJsonValidationErrors(['verify_url', 'credentials.client_id', 'credentials.client_secret', 'callback_data']);
});
