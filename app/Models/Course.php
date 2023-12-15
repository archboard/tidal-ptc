<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
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

    protected $guarded = [];

    public function scopeFilter(Builder $builder, array $filters = []): void
    {
        $builder->when($filters['search'] ?? null, function (Builder $builder, string $search) {
            $builder->search($search);
        })->orderBy($filters['sort'] ?? 'name', $filters['dir'] ?? 'asc');
    }

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
}
