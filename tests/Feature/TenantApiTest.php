<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected string $token;

    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asCloud();

        $this->token = Str::random();

        DB::table('machine_api_tokens')
            ->insert(['api_token' => hash('sha256', $this->token)]);

        $this->headers = [
            'Authorization' => "Bearer {$this->token}",
        ];
    }

    public function test_cant_access_cloud_endpoints()
    {
        $this->asSelfHosted()
            ->getJson('/api/tenants')
            ->assertNotFound();
    }

    public function test_need_machine_token_guard()
    {
        $this->getJson('/api/tenants')
            ->assertUnauthorized();
    }

    public function test_can_get_tenants()
    {
        Tenant::factory()->count(3)->create();

        $json = $this->getJson('/api/tenants', $this->headers)
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->json();

        $this->assertCount(4, $json['data']);
    }

    public function test_can_create_tenant()
    {
        Queue::fake();

        $data = [
            'name' => $this->faker->company(),
            'domain' => $this->faker->domainName(),
            'license' => $this->faker->uuid(),
            'subscription_started_at' => now()->subMonth()->toDateTimeString(),
            'subscription_expires_at' => now()->addYear()->toDateTimeString(),
        ];

        $this->postJson('/api/tenants', $data, $this->headers)
            ->assertCreated();

        $this->assertTrue(
            Tenant::query()->where('license', $data['license'])->exists()
        );
    }
}
