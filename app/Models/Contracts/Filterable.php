<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface Filterable
{
    /**
     * @param  Builder<Model>  $builder
     * @param  Collection<array-key, mixed>|array<array-key, mixed>  $data
     * @return Builder<Model>
     */
    public function scopeFilter(Builder $builder, Collection|array $data): Builder;
}
