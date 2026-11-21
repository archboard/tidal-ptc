<?php

use App\Enums\Role;
use App\Jobs\SyncSchools;
use App\Models\Tenant;
use App\Models\User;
use App\SisProviders\PowerSchoolProvider;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Uri;
use Inertia\Testing\AssertableInertia;

function removeSisConfig(): void
{
    test()->tenant->update(['sis_config' => null]);
}

function fakeLicenseValidation(bool $valid = true): void
{
    Http::fake(['archboard.io/verify/*' => Http::response(compact('valid'))]);
}

function powerSchoolInstallationRequest(array $attributes = []): array
{
    return [
        'name' => fake()->company(),
        'domain' => Uri::of(env('APP_URL'))->host(),
        'sis_config' => [
            'url' => env('POWERSCHOOL_ADDRESS'),
            'client_id' => env('POWERSCHOOL_CLIENT_ID'),
            'client_secret' => env('POWERSCHOOL_CLIENT_SECRET'),
        ],
        ...$attributes,
    ];
}

function failPowerSchoolCall(string $method): void
{
    $provider = test()->mock(PowerSchoolProvider::class);
    $provider->shouldReceive('configured')->andReturnTrue();
    $provider->shouldReceive($method)
        ->andThrow(new ClientException('Not Found', new Request('POST', '/ws/schema/query'), new Response(404)));
}

it('cannot be accessed on cloud', function () {
    $this->asCloud()
        ->get(route('install'))
        ->assertNotFound();
});

it('is accessible when installed but no users exist', function () {
    $this->asSelfHosted()
        ->get(route('install'))
        ->assertOk();
});

it('cannot be accessed without permission', function () {
    removeSisConfig();

    $this->logIn()
        ->get(route('install'))
        ->assertNotFound();
});

it('redirects guests to the installation when not installed', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->get('/login')
        ->assertRedirect(route('install'));
});

it('redirects users to the installation when not installed', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->logIn()
        ->get('/')
        ->assertRedirect(route('install'));
});

it('shows the installation page to guests', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->get(route('install'))
        ->assertViewHas('title')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('name')
            ->has('domain')
            ->has('sisConfig')
            ->has('sisOptions', 2)
            ->where('sisProvider', 'ps')
            ->component('Install')
        );
});

it('shows the installation page to authorized users', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->logIn()
        ->tapUser(fn (User $user) => $user->allow()->everything())
        ->get(route('install'))
        ->assertViewHas('title')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('name')
            ->has('domain')
            ->has('sisConfig')
            ->component('Install')
        );
});

it('installs without an existing tenant', function () {
    Queue::fake();
    $this->tenant->delete();
    Tenant::forgetCurrent();
    $data = powerSchoolInstallationRequest();
    fakeLicenseValidation();

    $this->asSelfHosted()
        ->post(route('install'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('install.user'));

    $this->assertDatabaseHas('tenants', Arr::only($data, ['name', 'domain']));
    expect(Tenant::firstWhere('domain', $data['domain'])->sis_config->toArray())->toEqual($data['sis_config']);
    Queue::assertPushed(SyncSchools::class);
});

it('installs over an existing tenant', function () {
    Queue::fake();
    removeSisConfig();
    $data = powerSchoolInstallationRequest();
    fakeLicenseValidation();

    $this->asSelfHosted()
        ->post(route('install'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('install.user'));

    $this->assertDatabaseHas('tenants', Arr::only($data, ['name', 'domain']));
    expect(Tenant::firstWhere('domain', $data['domain'])->sis_config->toArray())->toEqual($data['sis_config']);
    Queue::assertPushed(SyncSchools::class);
});

it('does not need uuids for a ClassLink config', function () {
    Queue::fake();
    removeSisConfig();
    fakeLicenseValidation();
    $data = powerSchoolInstallationRequest([
        'sis_provider' => 'class link',
        'sis_config' => [
            'url' => 'https://oneroster.test/ims/oneroster/rostering/v1p2',
            'token_url' => 'https://oneroster.test/token',
            'client_id' => 'plain-id',
            'client_secret' => 'plain-secret',
        ],
    ]);

    $this->asSelfHosted()
        ->post(route('install'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('install.user'));

    expect($this->tenant->fresh()->sis_provider->value)->toBe('class link');
    Queue::assertPushed(SyncSchools::class);
});

it('requires a token url for a ClassLink config', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->post(route('install'), powerSchoolInstallationRequest(['sis_provider' => 'class link']))
        ->assertSessionHasErrors('sis_config.token_url');
});

it('still requires uuids for a PowerSchool config', function () {
    removeSisConfig();
    $data = powerSchoolInstallationRequest([
        'sis_config' => ['url' => 'https://ps.test', 'client_id' => 'plain', 'client_secret' => 'plain'],
    ]);

    $this->asSelfHosted()
        ->post(route('install'), $data)
        ->assertSessionHasErrors(['sis_config.client_id', 'sis_config.client_secret']);
});

it('redirects user selection to the installation when not installed', function () {
    removeSisConfig();

    $this->asSelfHosted()
        ->get(route('install.user'))
        ->assertRedirect(route('install'));
});

it('blocks user selection when an admin already exists', function () {
    $this->seedUser()->assignRole(Role::DISTRICT_ADMIN);

    $this->asSelfHosted()
        ->get(route('install.user'))
        ->assertSessionHas('error')
        ->assertRedirect();
});

it('shows the user import page', function () {
    $this->asSelfHosted()
        ->get(route('install.user'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('InstallUser')
            ->where('endpoint', route('install.user'))
        );
});

it('asks to enable the plugin when the SIS user search fails', function () {
    Exceptions::fake();
    failPowerSchoolCall('searchForUser');

    $this->asSelfHosted()
        ->postJson('/search/sis/user', ['search' => 'jane'])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Could not connect to PowerSchool. Make sure the plugin is installed and enabled, then try again.']);

    Exceptions::assertNothingReported();
});

it('asks to enable the plugin when the first user sync fails', function () {
    Exceptions::fake();
    failPowerSchoolCall('syncUser');

    $this->asSelfHosted()
        ->from(route('install.user'))
        ->post(route('install.user'), ['user' => ['sis_id' => '123']])
        ->assertRedirect(route('install.user'))
        ->assertSessionHas('error', 'Could not connect to PowerSchool. Make sure the plugin is installed and enabled, then try again.');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['sis_id' => '123']);
    Exceptions::assertNothingReported();
});
