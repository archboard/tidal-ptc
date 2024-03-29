<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasHiddenAttribute;
use GrantHolle\ModelFilters\Filters\TextFilter;
use GrantHolle\ModelFilters\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
    use HasFilters;
    use HasHiddenAttribute;

    protected $guarded = [];

    protected $casts = [
        'can_book' => 'boolean',
    ];

    public function scopeSearch(Builder $builder, string $search): void
    {
        $builder->where(function (Builder $builder) use ($search) {
            $builder->where('section_number', 'ilike', "%{$search}%")
                ->orWhereHas('course', function (Builder $builder) use ($search) {
                    $builder->search($search);
                });
        });
    }

    public function teacherDisplay(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->relationLoaded('teacher') && $this->relationLoaded('altTeacher')) {
                if ($this->altTeacher) {
                    return "{$this->altTeacher->name} ({$this->teacher->name})";
                }

                return $this->teacher->name;
            }

            return null;
        });
    }

    public function teacherCanBook(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->relationLoaded('teacher') && $this->relationLoaded('altTeacher')) {
                if ($this->altTeacher) {
                    return $this->altTeacher->can_book;
                }

                return $this->teacher->can_book;
            }

            return true;
        });
    }

    public function display(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->relationLoaded('course')) {
                return "{$this->course->name} ({$this->section_number})";
            }

            return $this->section_number;
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function altTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alt_user_id');
    }

    public function syncFromSis(): static
    {
        return $this->tenant->getSisProvider()
            ->syncSection($this);
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
