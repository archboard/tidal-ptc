<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MachineTokenTest extends TestCase
{
    use RefreshDatabase;

    protected bool $cloud = true;

    public function test_self_hosted_cant_generate_token()
    {
        $this->asSelfHosted();

        $this->assertTrue(
            DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist()
        );

        $this->artisan('make:token')
            ->assertOk();

        $this->assertTrue(
            DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist()
        );
    }

    public function test_can_new_generate_token()
    {
        $this->assertTrue(
            DB::table('machine_api_tokens')->whereNotNull('api_token')->doesntExist()
        );

        $this->artisan('make:token')
            ->assertOk();

        $this->assertTrue(
            DB::table('machine_api_tokens')->whereNotNull('api_token')->exists()
        );
    }

    public function test_can_new_generate_token_over_existing_tokens()
    {
        DB::table('machine_api_tokens')->insert(['api_token' => 'existing']);

        $this->assertTrue(
            DB::table('machine_api_tokens')->whereNotNull('api_token')->exists()
        );

        $this->artisan('make:token')
            ->assertOk();

        $this->assertEquals(1, DB::table('machine_api_tokens')->whereNotNull('api_token')->count());
        $this->assertTrue(
            DB::table('machine_api_tokens')->where('api_token', 'existing')->doesntExist()
        );
    }
}
