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

    /** @return array<string, string> */
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

    /**
     * Providers that can be chosen. ClassLink is not implemented yet.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function selectOptions(): array
    {
        return array_map(fn (Sis $sis) => [
            'label' => $sis->label(),
            'value' => $sis->value,
        ], [self::PS]);
    }

    public function label(): string
    {
        return match ($this) {
            self::PS => 'PowerSchool SIS',
            self::CLASS_LINK => 'ClassLink',
        };
    }

    /**
     * Shown when an SIS API call fails during installation, usually because the plugin isn't enabled.
     */
    public function connectionErrorMessage(): string
    {
        return match ($this) {
            self::PS => __('Could not connect to PowerSchool. Make sure the plugin is installed and enabled, then try again.'),
            self::CLASS_LINK => __('Could not connect to the SIS. Check the configuration and try again.'),
        };
    }

    public function getProvider(Tenant $tenant): SisProvider
    {
        return match ($this) {
            // Prefer a container-bound instance so tests can swap in a mock
            self::PS => app()->bound(PowerSchoolProvider::class)
                ? app(PowerSchoolProvider::class)
                : new PowerSchoolProvider($tenant),
            self::CLASS_LINK => throw new \Exception('To be implemented'),
        };
    }

    /** @param Collection<array-key, mixed> $config */
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
