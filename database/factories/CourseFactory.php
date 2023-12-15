<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
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
            'name' => $this->faker->word(),
            'course_number' => $this->faker->numberBetween(1, 100),
            'sis_id' => $this->faker->numberBetween(1, 100),
        ];
    }
}
