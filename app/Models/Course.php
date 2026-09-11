<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Models\Contracts\Filterable;
use App\Services\Filters\BaseFilter;
use App\Services\Filters\TextFilter;
use App\Traits\BelongsToTenant;
use App\Traits\HasFilters;
use App\Traits\HasHiddenAttribute;
use Carbon\CarbonImmutable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property string $name
 * @property int $sis_id
 * @property string|null $course_number
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string $sis_key
 * @property bool $can_book
 * @property-read Collection<int, Section> $sections
 * @property-read int|null $sections_count
 * @property-read Tenant $tenant
 *
 * @method static Builder<static>|Course canBook()
 * @method static \Database\Factories\CourseFactory factory($count = null, $state = [])
 * @method static Builder<static>|Course filter(\Illuminate\Support\Collection<array-key, mixed>|array<array-key, mixed> $data)
 * @method static Builder<static>|Course newModelQuery()
 * @method static Builder<static>|Course newQuery()
 * @method static Builder<static>|Course query()
 * @method static Builder<static>|Course search(string $search)
 * @method static Builder<static>|Course whereCanBook($value)
 * @method static Builder<static>|Course whereCourseNumber($value)
 * @method static Builder<static>|Course whereCreatedAt($value)
 * @method static Builder<static>|Course whereId($value)
 * @method static Builder<static>|Course whereName($value)
 * @method static Builder<static>|Course whereSchoolId($value)
 * @method static Builder<static>|Course whereSisId($value)
 * @method static Builder<static>|Course whereSisKey($value)
 * @method static Builder<static>|Course whereTenantId($value)
 * @method static Builder<static>|Course whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Course extends Model implements ExistsInSis, Filterable
{
    use BelongsToTenant;

    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    use HasFilters;
    use HasHiddenAttribute;

    protected $guarded = [];

    protected $casts = [
        'can_book' => 'boolean',
    ];

    /** @param Builder<static> $builder */
    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where('course_number', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%");
        });
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function syncFromSis(): static
    {
        $this->tenant->getSisProvider()?->syncCourse($this);

        return $this;
    }

    /** @return array<int, BaseFilter> */
    public function filters(): array
    {
        return [
            TextFilter::make('search', __('Search'))
                ->hide()
                ->using($this->applySearchFilter(...)),
        ];
    }

    /** @param Builder<Course> $builder */
    protected function applySearchFilter(Builder $builder, string $search): void
    {
        $builder->search($search);
    }
}
