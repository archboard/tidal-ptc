<?php

namespace App\Enums\Traits;

use Illuminate\Support\Collection;

trait Collectable
{
    /**
     * @return Collection<int, static>
     */
    public static function collect(): Collection
    {
        return collect(static::allCases());
    }

    /**
     * @return array<int, static>
     */
    protected static function allCases(): array
    {
        return static::cases();
    }
}
