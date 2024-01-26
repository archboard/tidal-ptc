<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\HasFirstAndLastName;
use App\Traits\HasHiddenAttribute;
use GrantHolle\ModelFilters\Enums\Component;
use GrantHolle\ModelFilters\Filters\MultipleSelectFilter;
use GrantHolle\ModelFilters\Filters\TextFilter;
use GrantHolle\ModelFilters\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @mixin IdeHelperStudent
 */
class Student extends Model implements ExistsInSis
{
    use BelongsToSchool;
    use BelongsToTenant;
    use HasFactory;
    use HasFirstAndLastName;
    use HasHiddenAttribute;
    use SoftDeletes;
    use HasFilters;

    protected $guarded = [];

    protected $casts = [
        'hidden' => 'boolean',
    ];

//    public function scopeFilter(Builder $builder, array $filters = []): void
//    {
//        $sort = $filters['sort'] ?? 'last_name';
//        $dir = $filters['dir'] ?? 'asc';
//
//        $builder->when($filters['search'] ?? null, function (Builder $builder, string $search) {
//            $builder->search($search);
//        })->when($filters['grade'] ?? null, function (Builder $builder, $grade) {
//            $builder->whereIn('grade_level', Arr::wrap($grade));
//        });
//
//        $builder->orderBy($sort, $dir);
//    }

    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere(DB::raw("(first_name || ' ' || last_name)"), 'ilike', "%{$search}%")
                ->orWhere('student_number', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%");
        });
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['relationship']);
    }

    public function syncFromSis(): static
    {
        return $this->tenant->getSisProvider()
            ->syncStudent($this);
    }

    public function filters(): array
    {
        return [
            TextFilter::make('search', __('Search'))
                ->hide()
                ->using(fn (Builder $builder, string $search) => $builder->search($search)),
            TextFilter::make('first_name', __('First name')),
            TextFilter::make('last_name', __('Last name')),
            TextFilter::make('email', __('Email')),
            MultipleSelectFilter::make('grade_level', __('Grade level'))
                ->withComponent(Component::combobox)
                ->options(School::current()->gradeSelectOptions()),
        ];
    }
}
