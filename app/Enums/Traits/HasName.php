<?php

namespace App\Enums\Traits;

use App\Attributes\Name;
use ReflectionClassConstant;

trait HasName
{
    public function name(): string
    {
        $reflection = new ReflectionClassConstant(static::class, $this->name);
        $attributes = $reflection->getAttributes(Name::class);

        if (empty($attributes)) {
            return $this->name;
        }

        return $attributes[0]->newInstance()->name;
    }
}
