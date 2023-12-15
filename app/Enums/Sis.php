<?php

namespace App\Enums;

use App\Fields\FormField;
use App\Fields\FormFieldCollection;
use App\Models\Tenant;
use App\SisProviders\PowerSchoolProvider;
use App\SisProviders\SisProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

enum Sis: string
{
    case PS = 'ps';
    case CLASS_LINK = 'class link';

    public static function options(): array
    {
        return array_reduce(
            Sis::cases(),
            function (array $carry, Sis $sis) {
                $carry[$sis->value] = $sis->label();

                return $carry;
            },
            []
        );
    }

    public static function selectOptions(): array
    {
        return array_map(fn (Sis $sis) => [
            'label' => $sis->label(),
            'value' => $sis->value,
        ], Sis::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PS => 'PowerSchool SIS',
            self::CLASS_LINK => 'ClassLink',
        };
    }

    public function getProvider(Tenant $tenant): SisProvider
    {
        return match ($this) {
            self::PS => new PowerSchoolProvider($tenant),
            self::CLASS_LINK => throw new \Exception('To be implemented'),
        };
    }

    public function isConfigured(Collection $config): bool
    {
        return match ($this) {
            self::PS => $config->get('url') &&
                $config->get('client_id') &&
                $config->get('client_secret'),
            self::CLASS_LINK => false,
        };
    }

    public function getConfigFields(): FormFieldCollection
    {
        $fields = match ($this) {
            self::PS => [
                'url' => FormField::make(__('PowerSchool URL'))
                    ->type('url')
                    ->rules(['required', 'url']),
                'client_id' => FormField::make(__('PowerSchool Client ID'))
                    ->rules(['required', 'uuid']),
                'client_secret' => FormField::make(__('PowerSchool Client Secret'))
                    ->rules(['required', 'uuid']),
            ],
            self::CLASS_LINK => [],
        };

        return FormFieldCollection::make(Arr::prependKeysWith($fields, 'sis_config.'));
    }

    public function getRules(): array
    {
        return $this->getConfigFields()
            ->map(fn (FormField $field): array => $field->getRules())
            ->toArray();
    }
}
