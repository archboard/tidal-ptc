<?php

namespace Database\Factories;

use App\Enums\Sis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'domain' => env('TESTING_APP_URL'),
            'allow_password_auth' => true,
            'allow_oidc_login' => true,
            'subscription_started_at' => now(),
            'license' => $this->faker->uuid(),
            'sis_provider' => Sis::PS,
            'sis_config' => [
                'url' => env('POWERSCHOOL_ADDRESS'),
                'client_id' => env('POWERSCHOOL_CLIENT_ID'),
                'client_secret' => env('POWERSCHOOL_CLIENT_SECRET'),
            ],
        ];
    }
}
