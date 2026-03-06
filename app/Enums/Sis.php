<?php

namespace App\Enums;

use App\Models\Tenant;
use App\SisProviders\PowerSchoolProvider;
use App\SisProviders\SisProvider;
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
}
