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
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int $course_id
 * @property int $user_id
 * @property int $sis_id
 * @property string|null $section_number
 * @property string|null $expression
 * @property string|null $external_expression
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string $sis_key
 * @property bool $can_book
 * @property int|null $alt_user_id
 * @property-read \App\Models\User|null $altTeacher
 * @property-read \App\Models\Course $course
 * @property-read string $display
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students
 * @property-read int|null $students_count
 * @property-read \App\Models\User $teacher
 * @property-read bool $teacher_can_book
 * @property-read string|null $teacher_display
 * @property-read \App\Models\Tenant $tenant
 *
 * @method static Builder<static>|Section canBook()
 * @method static \Database\Factories\SectionFactory factory($count = null, $state = [])
 * @method static Builder<static>|Section filter(\Illuminate\Support\Collection|array $data)
 * @method static Builder<static>|Section newModelQuery()
 * @method static Builder<static>|Section newQuery()
 * @method static Builder<static>|Section query()
 * @method static Builder<static>|Section search(string $search)
 * @method static Builder<static>|Section whereAltUserId($value)
 * @method static Builder<static>|Section whereCanBook($value)
 * @method static Builder<static>|Section whereCourseId($value)
 * @method static Builder<static>|Section whereCreatedAt($value)
 * @method static Builder<static>|Section whereExpression($value)
 * @method static Builder<static>|Section whereExternalExpression($value)
 * @method static Builder<static>|Section whereId($value)
 * @method static Builder<static>|Section whereSchoolId($value)
 * @method static Builder<static>|Section whereSectionNumber($value)
 * @method static Builder<static>|Section whereSisId($value)
 * @method static Builder<static>|Section whereSisKey($value)
 * @method static Builder<static>|Section whereTenantId($value)
 * @method static Builder<static>|Section whereUpdatedAt($value)
 * @method static Builder<static>|Section whereUserId($value)
 *
 * @mixin \Eloquent
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
