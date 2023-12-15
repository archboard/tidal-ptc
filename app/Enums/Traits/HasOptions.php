<?php

namespace App\Enums\Traits;

trait HasOptions
{
    public static function options(): array
    {
        return array_reduce(
            static::cases(),
            function (array $carry, $sis) {
                $carry[$sis->value] = $sis->label();

                return $carry;
            },
            []
        );
    }

    public static function selectOptions(): array
    {
        return array_map(fn ($sis) => [
            'label' => $sis->label(),
            'value' => $sis->value,
        ], static::cases());
    }

    public function label(): string
    {
        return 'Default label';
    }
}
