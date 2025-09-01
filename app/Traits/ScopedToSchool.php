<?php

namespace App\Traits;

use App\Models\Scopes\SchoolScope;

trait ScopedToSchool
{
    public static function bootScopedToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);
    }
}
