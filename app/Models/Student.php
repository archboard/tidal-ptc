<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\HasFirstAndLastName;
use App\Traits\HasHiddenAttribute;
use App\Traits\HasTimeSlots;
use GrantHolle\ModelFilters\Enums\Component;
use GrantHolle\ModelFilters\Filters\MultipleSelectFilter;
use GrantHolle\ModelFilters\Filters\TextFilter;
use GrantHolle\ModelFilters\Traits\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int $sis_id
 * @property string|null $student_number
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string $sis_key
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property int|null $grade_level
 * @property bool $can_book
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $contacts
 * @property-read int|null $contacts_count
 * @property-read mixed $last_first
 * @property-read mixed $name
 * @property-read \App\Models\School $school
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Section> $sections
 * @property-read int|null $sections_count
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 *
 * @method static Builder<static>|Student canBook()
 * @method static \Database\Factories\StudentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Student filter(\Illuminate\Support\Collection|array $data)
 * @method static Builder<static>|Student newModelQuery()
 * @method static Builder<static>|Student newQuery()
 * @method static Builder<static>|Student onlyTrashed()
 * @method static Builder<static>|Student query()
 * @method static Builder<static>|Student search(string $search)
 * @method static Builder<static>|Student whereCanBook($value)
 * @method static Builder<static>|Student whereCreatedAt($value)
 * @method static Builder<static>|Student whereDeletedAt($value)
 * @method static Builder<static>|Student whereEmail($value)
 * @method static Builder<static>|Student whereFirstName($value)
 * @method static Builder<static>|Student whereGradeLevel($value)
 * @method static Builder<static>|Student whereId($value)
 * @method static Builder<static>|Student whereLastName($value)
 * @method static Builder<static>|Student whereSchoolId($value)
 * @method static Builder<static>|Student whereSisId($value)
 * @method static Builder<static>|Student whereSisKey($value)
 * @method static Builder<static>|Student whereStudentNumber($value)
 * @method static Builder<static>|Student whereTenantId($value)
 * @method static Builder<static>|Student whereUpdatedAt($value)
 * @method static Builder<static>|Student withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Student withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Student extends Model implements ExistsInSis
{
    use BelongsToSchool;
    use BelongsToTenant;
    use HasFactory;
    use HasFilters;
    use HasFirstAndLastName;
    use HasHiddenAttribute;
    use HasTimeSlots;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'can_book' => 'boolean',
    ];

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

    /** @return BelongsToMany<Section, $this> */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['relationship']);
    }

    public function syncFromSis(): static
    {
        $this->tenant->getSisProvider()?->syncStudent($this);

        return $this;
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
            MultipleSelectFilter::make('can_book', __('Booking status'))
                ->withComponent(Component::combobox)
                ->options([
                    true => __('Can book'),
                    false => __('Cannot book'),
                ]),
        ];
    }

    public function fullCalendarEventUrl(): string
    {
        return route('students.event-source', $this);
    }
}
