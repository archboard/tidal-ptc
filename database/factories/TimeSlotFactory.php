<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::current()?->id,
            'school_id' => School::current()?->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(15),
            'teacher_notes' => $this->faker->sentence(),
            'location' => $this->faker->word(),
            'meeting_url' => $this->faker->url(),
        ];
    }
}
