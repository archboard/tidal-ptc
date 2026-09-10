<?php

namespace App\Enums\Traits;

use App\Enums\Contracts\HasCustomName;
use Illuminate\Support\Str;

trait HasOptions
{
    /** @return array<string, string> */
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

    /** @return array<int, array<string, string>> */
    public static function selectOptions(): array
    {
        return array_map(fn ($sis) => [
            'label' => $sis->label(),
            'value' => $sis->value,
        ], static::cases());
    }

    public function label(): string
    {
        if ($this instanceof HasCustomName) {
            return $this->name();
        }

        return Str::of($this->value)
            ->replace(['_', '-'], '')
            ->lower()
            ->ucfirst()
            ->toString();
    }
}
