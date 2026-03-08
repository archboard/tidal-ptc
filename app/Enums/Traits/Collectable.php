<?php

namespace App\Enums\Traits;

use Illuminate\Support\Collection;

trait Collectable
{
    /**
     * @return Collection<int, self>
     */
    public static function collect(): Collection
    {
        return collect(static::cases());
    }
}
