<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasHiddenAttribute
{
    /** @param Builder<static> $builder */
    public function scopeCanBook(Builder $builder): void
    {
        $builder->where('can_book', true);
    }

    public function toggleHiddenFlag(): static
    {
        $this->can_book = ! $this->can_book;

        return $this;
    }
}
