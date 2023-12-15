<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperSection
 */
class Section extends Model implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;

    protected $guarded = [];

    public function scopeFilter(Builder $builder, array $filters = []): void
    {
        $builder->select('sections.*')
            ->when($filters['search'] ?? null, function (Builder $builder, string $search) {
                $builder->search($search);
            })
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->orderBy('courses.name');
    }

    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where('section_number', 'ilike', "%{$search}%")
                ->orWhereHas('course', function (Builder $builder) use ($search) {
                    $builder->search($search);
                });
        });
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function syncFromSis(): static
    {
        return $this->tenant->getSisProvider()
            ->syncSection($this);
    }
}
