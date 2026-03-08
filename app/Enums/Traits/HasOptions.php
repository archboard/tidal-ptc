<?php

namespace App\Enums\Traits;

use Illuminate\Support\Str;

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
        if (method_exists($this, 'name')) {
            return $this->name();
        }

        return Str::of($this->value)
            ->replace(['_', '-'], '')
            ->lower()
            ->ucfirst()
            ->toString();
    }
}
