<?php

use App\Enums\Role;
use App\Jobs\SyncSchools;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->asCloud();
    $this->tenant->update(['sis_config' => null]);
});

it('opens the install wizard from a setup link and installs the current tenant', function () {
    Queue::fake();
    $domain = $this->tenant->domain;

    $this->get($this->tenant->setupLinks()['setup_url'])
        ->assertRedirect('/install');
    $this->get('/install')
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Install')->where('isCloud', true));
    $this->post(route('install'), [
        'name' => 'Test District',
        'domain' => 'hijacked.example.com',
        'sis_config' => [
            'url' => 'https://ps.example.com',
            'client_id' => fake()->uuid(),
            'client_secret' => fake()->uuid(),
        ],
    ])->assertRedirect(route('install.user'));

    expect($this->tenant->fresh())
        ->name->toBe('Test District')
        ->domain->toBe($domain)
        ->and(Tenant::count())->toBe(1);
    Queue::assertPushed(SyncSchools::class);
});

it('sends an installed tenant without an admin to the first user step', function () {
    $this->tenant->update(['sis_config' => Tenant::factory()->make()->sis_config]);

    $this->get($this->tenant->setupLinks()['setup_url'])
        ->assertRedirect(route('install.user'));
});

it('returns 403 for a tampered setup link', function () {
    $url = str_replace('signature=', 'signature=x', $this->tenant->setupLinks()['setup_url']);

    $this->get($url)->assertForbidden();
    $this->get('/install')->assertNotFound();
});

it('returns 403 for an expired setup link', function () {
    $url = $this->tenant->setupLinks()['setup_url'];
    $this->travel(15)->days();

    $this->get($url)->assertForbidden();
});

it('returns 404 for another tenant\'s setup link', function () {
    $other = Tenant::factory()->create(['domain' => 'other.example.com']);

    $this->get($other->setupLinks()['setup_url'])->assertNotFound();
    $this->get('/install')->assertNotFound();
});

it('returns 404 for setup links once a district admin exists', function (string $link) {
    seedUser()->assignRole(Role::DISTRICT_ADMIN);

    $this->get($this->tenant->setupLinks()[$link])->assertNotFound();
})->with(['setup_url', 'plugin_url']);

it('shows a setup-in-progress page when an uninstalled tenant is visited without a link', function () {
    $this->get('/login')
        ->assertServiceUnavailable()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->has('message'));
});

it('downloads the plugin from a plugin link', function () {
    $this->get($this->tenant->setupLinks()['plugin_url'])
        ->assertDownload('tidal-ptc-plugin.zip');
});
