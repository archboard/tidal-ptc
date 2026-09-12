<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Models\School;
use App\Models\Tenant;
use App\Models\Translator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translator>
 */
class TranslatorFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::current()?->id,
            'school_id' => School::current()?->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'languages' => [Language::JAPANESE],
            'active' => true,
        ];
    }
}
