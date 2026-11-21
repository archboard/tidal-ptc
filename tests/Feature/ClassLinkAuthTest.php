<?php

use App\Enums\Sis;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function classLinkToken(array $claims = []): string
{
    $b64 = fn (string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    $head = $b64(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => 'k1']));
    $body = $b64(json_encode($claims + [
        'iss' => 'https://broker.test',
        'aud' => request()->getSchemeAndHttpHost(),
        'app' => 'tidal-ptc',
        'nonce' => 'nonce-1',
        'iat' => now()->getTimestamp(),
        'exp' => now()->getTimestamp() + 60,
        'jti' => fake()->uuid(),
        'idp_tenant_id' => 'district-1',
        'role' => 'staff',
        'email' => 'Teacher@Example.com',
    ]));

    return "$head.$body.".$b64(sodium_crypto_sign_detached("$head.$body", test()->secretKey));
}

beforeEach(function () {
    $pair = sodium_crypto_sign_keypair();
    $this->secretKey = sodium_crypto_sign_secretkey($pair);
    config([
        'services.auth_broker.url' => 'https://broker.test',
        'services.auth_broker.public_keys' => ['k1' => base64_encode(sodium_crypto_sign_publickey($pair))],
    ]);
    $this->tenant->update(['classlink_tenant_id' => 'district-1']);
    $this->member = User::factory()->for($this->tenant)->create(['email' => 'teacher@example.com', 'user_type' => UserType::staff]);
});

function classLinkCallback(string $purpose, array $claims = [], string $nonce = 'nonce-1')
{
    return test()
        ->withSession(['classlink.nonce' => $nonce, 'classlink.purpose' => $purpose])
        ->get(route('classlink.callback', ['assertion' => classLinkToken($claims)]));
}

it('redirects to the broker with a nonce and the install origin', function () {
    $response = $this->get(route('classlink.authenticate'));

    $location = $response->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://broker.test/tidal-ptc/classlink/start?')
        ->and($query['origin'])->toBe(request()->getSchemeAndHttpHost())
        ->and($query['nonce'])->toBe(session('classlink.nonce'))
        ->and(session('classlink.purpose'))->toBe('login');
});

it('has no broker redirect without a broker url', function () {
    config(['services.auth_broker.url' => null]);

    $this->get(route('classlink.authenticate'))->assertNotFound();
});

it('forbids starting a connect without permission', function () {
    logIn();

    $this->get(route('classlink.connect'))->assertForbidden();
});

it('lets admins start a connect', function () {
    $this->asCloud();
    logIn();
    fullPermissions();
    $this->get(route('classlink.connect'))->assertRedirectContains('broker.test');
    expect(session('classlink.purpose'))->toBe('connect');
});

it('logs in staff and guardians as the account of their user type', function (UserType $type) {
    $guardian = User::factory()->for($this->tenant)->create(['email' => 'teacher@example.com', 'user_type' => UserType::guardian]);

    classLinkCallback('login', ['role' => $type->value])->assertRedirect('/');

    $this->assertAuthenticatedAs($type === UserType::staff ? $this->member : $guardian);
})->with([UserType::staff, UserType::guardian]);

it('finds the user by sis id when the tenant roster comes from ClassLink', function () {
    $this->tenant->update(['sis_provider' => Sis::CLASS_LINK, 'sis_config' => [
        'url' => 'https://oneroster.test/rostering', 'token_url' => 'https://oneroster.test/token', 'client_id' => 'id', 'client_secret' => 'secret',
    ]]);
    $this->member->update(['sis_id' => 'sourced-1', 'email' => 'other@example.com']);

    classLinkCallback('login', ['sub' => 'sourced-1']);

    $this->assertAuthenticatedAs($this->member);
});

it('ignores the sub on a PowerSchool tenant', function () {
    $this->tenant->update(['sis_provider' => Sis::PS]);
    $this->member->update(['sis_id' => 'sourced-1', 'email' => 'other@example.com']);

    classLinkCallback('login', ['sub' => 'sourced-1'])->assertRedirect(route('login'));

    $this->assertGuest();
});

it('rejects a failed login', function (array $claims, string $nonce) {
    classLinkCallback('login', $claims, $nonce)
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
})->with([
    'other district' => [['idp_tenant_id' => 'district-2'], 'nonce-1'],
    'student' => [['role' => 'student'], 'nonce-1'],
    'unknown user' => [['email' => 'nobody@example.com'], 'nonce-1'],
    'no account of that user type' => [['role' => 'guardian'], 'nonce-1'],
    'nonce mismatch' => [[], 'nonce-2'],
]);

it('rejects a login when the tenant has no classlink id', function () {
    $this->tenant->update(['classlink_tenant_id' => null]);

    classLinkCallback('login')->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects a replayed assertion', function () {
    classLinkCallback('login', ['jti' => 'same']);
    auth()->logout();

    classLinkCallback('login', ['jti' => 'same'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects a broker error', function () {
    $this->get(route('classlink.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('saves the classlink id when an admin connects', function () {
    $this->tenant->update(['classlink_tenant_id' => null]);
    logIn();
    fullPermissions();

    classLinkCallback('connect', ['idp_tenant_id' => 'district-9'])
        ->assertRedirect(route('settings.tenant.edit'))
        ->assertSessionHas('success');

    expect($this->tenant->refresh()->classlink_tenant_id)->toBe('district-9');
});

it('refuses to connect for a non-admin', function () {
    $this->tenant->update(['classlink_tenant_id' => null]);
    logIn();

    classLinkCallback('connect', ['idp_tenant_id' => 'district-9'])->assertSessionHasErrors('email');

    expect($this->tenant->refresh()->classlink_tenant_id)->toBeNull();
});

it('serves the cached broker challenge', function () {
    Cache::put('auth_broker.challenge', 'token-123');

    $this->get('/.well-known/auth-broker-challenge/tidal-ptc')->assertOk()->assertSee('token-123');
});

it('404s the challenge without a cached token', function () {
    $this->get('/.well-known/auth-broker-challenge/tidal-ptc')->assertNotFound();
});

it('tells the login page whether ClassLink is connected', function (?string $id, bool $expected) {
    $this->tenant->update(['classlink_tenant_id' => $id]);

    $this->get('/login')->assertInertia(fn ($page) => $page->where('tenant.allow_classlink_login', $expected));
})->with([['district-1', true], [null, false]]);

it('registers a self-hosted origin before connecting', function () {
    Http::fake(['https://broker.test/api/apps/tidal-ptc/registrations' => Http::response([], 201)]);
    $this->asSelfHosted();
    $license = fake()->uuid();
    $this->tenant->update(['license' => $license]);
    logIn();
    fullPermissions();

    $this->get(route('classlink.connect'))->assertRedirectContains('broker.test/tidal-ptc/classlink/start');

    Http::assertSent(fn ($request) => $request['license'] === $license
        && $request['origin'] === request()->getSchemeAndHttpHost());
});

it('does not connect when the self-hosted registration fails', function () {
    Http::fake(['https://broker.test/*' => Http::response('Invalid license', 422)]);
    $this->asSelfHosted();
    $license = fake()->uuid();
    $this->tenant->update(['license' => $license]);
    logIn();
    fullPermissions();

    $this->get(route('classlink.connect'))
        ->assertRedirect(route('settings.tenant.edit'))
        ->assertSessionHas('error', fn ($error) => str_contains($error, 'Invalid license'));
});

it('requires a license to connect on a self-hosted install', function () {
    Http::fake();
    $this->asSelfHosted();
    $this->tenant->update(['license' => null]);
    logIn();
    fullPermissions();

    $this->get(route('classlink.connect'))
        ->assertRedirect(route('settings.tenant.edit'))
        ->assertSessionHas('error');
    Http::assertNothingSent();
});

it('skips registration when connecting on cloud', function () {
    Http::fake();
    $this->asCloud();
    logIn();
    fullPermissions();

    $this->get(route('classlink.connect'))->assertRedirectContains('broker.test');
    Http::assertNothingSent();
});

it('shares the connected ClassLink tenant on the settings page', function () {
    logIn();
    fullPermissions();

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn ($page) => $page->where('tenant.classlink_tenant_id', 'district-1'));
});
