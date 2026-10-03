<?php

use App\Enums\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    $this->license = '5f0c6a8e-3c1b-4a55-9d0e-2b7f1c9a4e11';
    $this->body = [
        'name' => 'Springfield Public Schools',
        'domain' => 'springfield.example.com',
        'school_limit' => 5,
        'subscription_started_at' => '2026-10-01 00:00:00',
        'subscription_expires_at' => '2027-10-01 00:00:00',
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

it('creates a tenant for a new license and returns signed setup links on its domain', function () {
    $response = $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers)
        ->assertCreated();

    expect(Tenant::firstWhere('license', $this->license))
        ->name->toBe('Springfield Public Schools')
        ->domain->toBe('springfield.example.com')
        ->school_limit->toBe(5);
    foreach (['setup_url', 'plugin_url'] as $link) {
        expect($response->json($link))->toStartWith('https://springfield.example.com/setup/')
            ->and(URL::hasValidRelativeSignature(Request::create($response->json($link))))->toBeTrue();
    }
});

it('updates the existing tenant for a known license', function () {
    $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers);

    $this->putJson("/api/tenants/{$this->license}", [...$this->body, 'school_limit' => 8, 'subscription_expires_at' => '2028-10-01 00:00:00'], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.school_limit', 8);

    expect(Tenant::where('license', $this->license)->sole())
        ->school_limit->toBe(8)
        ->subscription_expires_at->toStartWith('2028-10-01');
});

it('stops returning setup links once the district has an admin', function () {
    $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers);
    $tenant = Tenant::firstWhere('license', $this->license);
    $tenant->execute(fn () => seedUser(['tenant_id' => $tenant->id])->assignRole(Role::DISTRICT_ADMIN));

    $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers)
        ->assertOk()
        ->assertJsonPath('setup_url', null)
        ->assertJsonPath('plugin_url', null);
});

it('lets the domain change only until the district has an admin', function () {
    $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers);
    $this->putJson("/api/tenants/{$this->license}", [...$this->body, 'domain' => 'springfield-ps.example.com'], $this->headers)
        ->assertOk();
    $tenant = Tenant::firstWhere('license', $this->license);
    $tenant->execute(fn () => seedUser(['tenant_id' => $tenant->id])->assignRole(Role::DISTRICT_ADMIN));

    $this->putJson("/api/tenants/{$this->license}", $this->body, $this->headers)
        ->assertJsonValidationErrors('domain');

    expect($tenant->fresh()->domain)->toBe('springfield-ps.example.com');
});

it('rejects a domain another tenant uses', function () {
    $this->putJson("/api/tenants/{$this->license}", [...$this->body, 'domain' => $this->tenant->domain], $this->headers)
        ->assertJsonValidationErrors('domain');

    expect(Tenant::count())->toBe(1);
});

it('returns 404 for a license that is not a UUID', function () {
    $this->putJson('/api/tenants/not-a-uuid', $this->body, $this->headers)
        ->assertNotFound();
});
