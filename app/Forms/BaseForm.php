<?php

namespace App\Forms;

use App\Fields\FormFieldCollection;
use App\Forms\Contracts\AppForm;
use Illuminate\Support\Str;

abstract class BaseForm implements AppForm
{
    public function endpoint(): string
    {
        return Str::of(class_basename($this))
            ->snake()
            ->studly()
            ->toString();
    }

    public function method(): string
    {
        return '';
    }

    public function title(): string
    {
        return Str::of(class_basename($this))
            ->replace('Form', '')
            ->snake()
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function fields(): FormFieldCollection
    {
        return FormFieldCollection::make();
    }

    public function rules(): array
    {
        return $this->fields()
            ->toValidationRules();
    }

    public function toInertia(?string $method = null, ?string $endpoint = null): array
    {
        return [
            'title' => $this->title(),
            'endpoint' => $endpoint ?? $this->endpoint(),
            'fields' => $this->fields()
                ->toResource(),
            'values' => $this->fields()
                ->toInertia()
                ->put('_method', $method ?? $this->method()),
        ];
    }
}
