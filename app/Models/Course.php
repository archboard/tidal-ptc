<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasHiddenAttribute;
use GrantHolle\ModelFilters\Filters\TextFilter;
use GrantHolle\ModelFilters\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCourse
 */
class Course extends Model implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
    use HasFilters;
    use HasHiddenAttribute;

    protected $guarded = [];

    protected $casts = [
        'can_book' => 'boolean',
    ];

    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where('course_number', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%");
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function syncFromSis(): static
    {
        return $this->tenant->getSisProvider()
            ->syncCourse($this);
    }

    public function filters(): array
    {
        return [
            TextFilter::make('search', __('Search'))
                ->hide()
                ->using(fn (Builder $builder, string $search) => $builder->search($search)),
        ];
    }
}
