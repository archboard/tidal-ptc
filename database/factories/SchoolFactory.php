<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = School::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'sis_id' => $this->faker->numberBetween(),
            'school_number' => $this->faker->numberBetween(),
            'name' => $this->faker->company,
            'high_grade' => 12,
            'low_grade' => $this->faker->numberBetween(-2, 0),
            'sis_key' => $this->faker->numberBetween(1, 100).Str::random(4),
        ];
    }
}
