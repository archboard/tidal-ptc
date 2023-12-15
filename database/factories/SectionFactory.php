<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
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
            'sis_id' => $this->faker->numberBetween(1, 100),
            'section_number' => $this->faker->numberBetween(1, 100),
            'expression' => $this->faker->word(),
            'external_expression' => $this->faker->word(),
            'sis_key' => $this->faker->numberBetween(1, 100),
        ];
    }
}
