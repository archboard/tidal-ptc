<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::current()->id,
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'sis_key' => $this->faker->numberBetween(1, 100),
        ];
    }
}
