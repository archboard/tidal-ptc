<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasHiddenAttribute
{
    public function scopeCanBook(Builder $builder): void
    {
        $builder->where('hidden', false);
    }
}
