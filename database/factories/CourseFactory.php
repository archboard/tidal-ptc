<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\School;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
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
            'school_id' => School::current()->id,
            'name' => $this->faker->word(),
            'course_number' => $this->faker->numberBetween(1, 100),
            'sis_id' => $this->faker->numberBetween(1, 100),
            'sis_key' => $this->faker->uuid(),
        ];
    }
}
