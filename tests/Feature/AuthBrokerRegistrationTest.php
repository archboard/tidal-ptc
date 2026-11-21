<?php

use App\Jobs\SyncSchools;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    config(['services.auth_broker.url' => 'https://broker.test']);
    $this->license = fake()->uuid();
    $this->tenant->update(['sis_config' => null]);
    $this->asSelfHosted();
    $this->payload = fn (array $extra = []) => [
        'name' => 'District',
        'domain' => $this->tenant->domain,
        'sis_config' => ['url' => 'https://ps.test', 'client_id' => fake()->uuid(), 'client_secret' => fake()->uuid()],
        ...$extra,
    ];
});

it('registers the origin with the broker when opted in', function () {
    $challengeAtCall = null;
    Http::fake([
        'archboard.io/verify/*' => Http::response(['valid' => true]),
        'broker.test/*' => function ($request) use (&$challengeAtCall) {
            $challengeAtCall = Cache::get('auth_broker.challenge');

            return Http::response('', 204);
        },
    ]);

    $this->post(route('install'), ($this->payload)(['enable_classlink' => true, 'license' => $this->license]))
        ->assertRedirect(route('install.user'))
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'ClassLink sign-in is enabled.'))
        ->assertSessionMissing('error');

    Http::assertSent(fn ($request) => $request->url() === 'https://broker.test/api/apps/tidal-ptc/registrations'
        && $request['license'] === $this->license
        && $request['origin'] === request()->getSchemeAndHttpHost()
        && $request['token'] === $challengeAtCall
        && strlen($challengeAtCall) === 48);
    expect(Cache::has('auth_broker.challenge'))->toBeFalse()
        ->and($this->tenant->fresh()->license)->toBe($this->license);
});

it('shows the broker error without failing the install', function () {
    Http::fake([
        'archboard.io/verify/*' => Http::response(['valid' => true]),
        'broker.test/*' => Http::response('challenge mismatch', 422),
    ]);

    $this->post(route('install'), ($this->payload)(['enable_classlink' => true, 'license' => $this->license]))
        ->assertRedirect(route('install.user'))
        ->assertSessionHas('error', 'ClassLink registration failed: challenge mismatch')
        ->assertSessionHas('success');

    Queue::assertPushed(SyncSchools::class);
    expect($this->tenant->fresh()->sis_config)->not->toBeNull()
        ->and(Cache::has('auth_broker.challenge'))->toBeFalse();
});

it('requires a license when opting in', function () {
    Http::fake();

    $this->post(route('install'), ($this->payload)(['enable_classlink' => true]))
        ->assertSessionHasErrors('license');

    Http::assertNothingSent();
});

it('sends nothing to the broker when not opted in', function () {
    Http::fake();

    $this->post(route('install'), ($this->payload)())
        ->assertRedirect(route('install.user'));

    Http::assertNothingSent();
});
