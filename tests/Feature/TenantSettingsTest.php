<?php

use App\Enums\Sis;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it("can't be accessed without permission", function () {
    $this->get(route('settings.tenant.edit'))
        ->assertForbidden();
});

it('can be accessed with permission', function () {
    fullPermissions();

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('tenant')
            ->has('smtp')
            ->has('schools')
        )
        ->assertOk();
});

it('can update smtp settings', function () {
    fullPermissions();

    $data = [
        'host' => fake()->domainName(),
        'port' => fake()->randomElement([587, 465, 25, 2525]),
        'username' => fake()->email(),
        'password' => fake()->word(),
        'from_name' => fake()->name(),
        'from_address' => fake()->email(),
        'encryption' => fake()->randomElement(['tls', 'ssl']),
    ];

    $this->put(route('settings.tenant.smtp'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('settings.tenant.edit'));

    $this->tenant->refresh();
    $this->assertEquals($data, $this->tenant->smtp_config->toArray());
});

it('lets cloud tenants keep the app-wide mailer without smtp settings', function () {
    fullPermissions();
    $this->asCloud();

    $this->put(route('settings.tenant.smtp'), ['custom' => false])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.tenant.edit'));

    expect($this->tenant->refresh()->smtp_config->get('custom'))->toBeFalse();
});

it('requires smtp settings when a cloud tenant uses its own provider', function () {
    fullPermissions();
    $this->asCloud();

    $this->put(route('settings.tenant.smtp'), ['custom' => true])
        ->assertSessionHasErrors(['host', 'port', 'from_name', 'from_address']);
});

it('shows the licensed school count in the cloud', function () {
    fullPermissions();
    $this->asCloud();
    $this->tenant->update(['school_limit' => 5]);

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('schoolLimit', 5));
});

it('activates the chosen schools within the limit in the cloud', function () {
    fullPermissions();
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    [$chosen, $other] = $this->tenant->schools;

    $this->put(route('settings.tenant.schools'), ['schools' => [$chosen->id]])
        ->assertSessionHas('success');

    expect($chosen->fresh()->active)->toBeTrue()
        ->and($other->fresh()->active)->toBeFalse();
});

it('moves the current school to an accessible active school when it is deactivated', function () {
    fullPermissions();
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    [$chosen, $deactivated] = $this->tenant->schools;
    $this->user->schools()->sync([$chosen->id, $deactivated->id]);
    $this->user->update(['school_id' => $deactivated->id]);

    $this->put(route('settings.tenant.schools'), ['schools' => [$chosen->id]])
        ->assertSessionHas('success');

    expect($this->user->fresh()->school_id)->toBe($chosen->id);
});

it('keeps the current school when it stays active', function () {
    fullPermissions();
    [$current, $other] = $this->tenant->schools;
    $this->user->schools()->sync([$current->id, $other->id]);
    $this->user->update(['school_id' => $current->id]);

    $this->put(route('settings.tenant.schools'), ['schools' => [$other->id, $current->id]])
        ->assertSessionHas('success');

    expect($this->user->fresh()->school_id)->toBe($current->id);
});

it('rejects activating more schools than the limit in the cloud', function () {
    fullPermissions();
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    $this->tenant->schools()->update(['active' => false]);

    $this->put(route('settings.tenant.schools'), ['schools' => $this->tenant->schools->pluck('id')->all()])
        ->assertSessionHasErrors('schools');

    expect($this->tenant->schools()->where('active', true)->exists())->toBeFalse();
});

it('accepts ClassLink as the SIS provider', function () {
    fullPermissions();

    $this->put(route('settings.tenant.update'), classLinkSettings())
        ->assertSessionDoesntHaveErrors();

    expect($this->tenant->fresh()->sis_provider)->toBe(Sis::CLASS_LINK);
});

it('can update tenant settings', function () {
    fullPermissions();

    $data = [
        'name' => fake()->company(),
        'domain' => fake()->domainName(),
        'sis_provider' => Sis::PS->value,
        'allow_password_auth' => fake()->boolean(),
        'allow_oidc_login' => fake()->boolean(),
    ];

    $this->put(route('settings.tenant.update'), $data)
        ->assertSessionHas('success')
        ->assertRedirect(route('settings.tenant.edit'));

    $this->tenant->refresh();
    $this->assertEquals($data['name'], $this->tenant->name);
    $this->assertEquals($data['domain'], $this->tenant->domain);
    $this->assertEquals($data['sis_provider'], $this->tenant->sis_provider->value);
    $this->assertEquals($data['allow_password_auth'], $this->tenant->allow_password_auth);
    $this->assertEquals($data['allow_oidc_login'], $this->tenant->allow_oidc_login);
});

function classLinkSettings(array $config = []): array
{
    return [
        'name' => 'Tenant',
        'domain' => 'tenant.test',
        'sis_provider' => Sis::CLASS_LINK->value,
        'sis_config' => [
            'url' => 'https://oneroster.test/ims/oneroster/rostering/v1p2',
            'token_url' => 'https://oneroster.test/token',
            'client_id' => 'new-id',
            'client_secret' => 'new-secret',
            ...$config,
        ],
    ];
}

it('saves ClassLink credentials and forgets the cached token', function () {
    fullPermissions();
    $this->tenant->update(['sis_config' => [...$this->tenant->sis_config, 'client_id' => 'old-id']]);
    $key = 'classlink.token.'.$this->tenant->id.'.'.md5('old-id');
    Cache::put($key, 'stale');

    $this->put(route('settings.tenant.update'), classLinkSettings())
        ->assertSessionHasNoErrors();

    $this->tenant->refresh();
    expect($this->tenant->sis_provider)->toBe(Sis::CLASS_LINK)
        ->and($this->tenant->sis_config->get('client_secret'))->toBe('new-secret')
        ->and(Cache::has($key))->toBeFalse();
});

it('requires credentials when switching to ClassLink', function () {
    fullPermissions();

    $this->put(route('settings.tenant.update'), ['sis_provider' => Sis::CLASS_LINK->value] + classLinkSettings(['url' => null, 'client_secret' => null]))
        ->assertSessionHasErrors(['sis_config.url', 'sis_config.client_secret']);
});

it('keeps the stored ClassLink secret when the secret is blank', function () {
    fullPermissions();
    $this->tenant->update([
        'sis_provider' => Sis::CLASS_LINK,
        'sis_config' => classLinkSettings(['client_secret' => 'kept'])['sis_config'],
    ]);

    $this->put(route('settings.tenant.update'), classLinkSettings(['client_id' => 'rotated', 'client_secret' => null]))
        ->assertSessionHasNoErrors();

    $config = $this->tenant->refresh()->sis_config;
    expect($config->get('client_secret'))->toBe('kept')
        ->and($config->get('client_id'))->toBe('rotated');
});

it('does not expose the ClassLink secret on the edit page', function () {
    fullPermissions();
    $this->tenant->update([
        'sis_provider' => Sis::CLASS_LINK,
        'sis_config' => classLinkSettings(['client_secret' => 'hidden'])['sis_config'],
    ]);

    $response = $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('classLinkConfig.client_id', 'new-id')
            ->missing('classLinkConfig.client_secret')
            ->where('hasClassLinkSecret', true)
        );

    expect($response->getContent())->not->toContain('hidden');
});

it('leaves sis_config alone when saving PowerSchool', function () {
    fullPermissions();
    $this->tenant->update(['sis_config' => [...$this->tenant->sis_config, 'client_id' => 'ps-id']]);

    $this->put(route('settings.tenant.update'), [
        'name' => 'Tenant',
        'domain' => 'tenant.test',
        'sis_provider' => Sis::PS->value,
    ])->assertSessionHasNoErrors();

    expect($this->tenant->refresh()->sis_config->get('client_id'))->toBe('ps-id');
});
