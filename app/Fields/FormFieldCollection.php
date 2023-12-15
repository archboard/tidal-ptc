<?php

namespace App\Fields;

use Illuminate\Support\Collection;

class FormFieldCollection extends Collection
{
    public function toResource(): static
    {
        return $this->map(fn (FormField $field) => $field->toResource())
            ->values();
    }

    public function toInertia(): Collection
    {
        return $this->map(fn (FormField $field) => $field->getValue());
    }

    public function toValidationRules(): array
    {
        return $this->reduce(function (array $rules, FormField $field, string $key) {
            // Since the dot notation doesn't mean that it's an array,
            // we have to escape the dot in the validation rule
            $escapedKey = implode('\.', explode('.', $key));
            $rules[$escapedKey] = $field->getRules();

            return $rules;
        }, []);
    }
}
