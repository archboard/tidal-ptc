<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasTimeSlots;
use App\Traits\HasTimezone;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $sis_id
 * @property int|null $school_number
 * @property string $name
 * @property int|null $high_grade
 * @property int|null $low_grade
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property bool $active
 * @property string $sis_key
 * @property string|null $timezone
 * @property \Carbon\CarbonImmutable|null $open_for_contacts_at
 * @property \Carbon\CarbonImmutable|null $close_for_contacts_at
 * @property \Carbon\CarbonImmutable|null $open_for_teachers_at
 * @property \Carbon\CarbonImmutable|null $close_for_teachers_at
 * @property bool $allow_online_meetings
 * @property bool $allow_translator_requests
 * @property int $booking_buffer_hours
 * @property-read bool $contacts_can_book
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Course> $courses
 * @property-read int|null $courses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SchoolLanguage> $languages
 * @property-read int|null $languages_count
 * @property-read mixed $local_close_for_contacts_at
 * @property-read mixed $local_close_for_teachers_at
 * @property-read mixed $local_open_for_contacts_at
 * @property-read mixed $local_open_for_teachers_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Section> $sections
 * @property-read int|null $sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Student> $students
 * @property-read int|null $students_count
 * @property-read bool $teachers_can_create
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School active()
 * @method static \Database\Factories\SchoolFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereAllowOnlineMeetings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereAllowTranslatorRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereBookingBufferHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereCloseForContactsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereCloseForTeachersAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereHighGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereLowGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereOpenForContactsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereOpenForTeachersAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereSchoolNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereSisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereSisKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|School whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class School extends Model implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
    use HasTimeSlots;
    use HasTimezone;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'allow_online_meetings' => 'boolean',
        'allow_translator_requests' => 'boolean',
        'open_for_contacts_at' => 'datetime',
        'close_for_contacts_at' => 'datetime',
        'open_for_teachers_at' => 'datetime',
        'close_for_teachers_at' => 'datetime',
        'booking_buffer_hours' => 'integer',
    ];

    public function scopeActive(Builder $builder): void
    {
        $builder->where('active', true);
    }

    public function contactsCanBook(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! $this->open_for_contacts_at && ! $this->close_for_contacts_at) {
                return false;
            }

            return (! $this->open_for_contacts_at || now()->gte($this->open_for_contacts_at))
                && (! $this->close_for_contacts_at || now()->lte($this->close_for_contacts_at));
        });
    }

    public function teachersCanCreate(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! $this->open_for_teachers_at && ! $this->close_for_teachers_at) {
                return false;
            }

            return (! $this->open_for_teachers_at || now()->gte($this->open_for_teachers_at))
                && (! $this->close_for_teachers_at || now()->lte($this->close_for_teachers_at));
        });
    }

    public function localOpenForContactsAt(): Attribute
    {
        return Attribute::get(fn () => $this->open_for_contacts_at
            ? $this->dateFromApp($this->open_for_contacts_at)
            : null
        );
    }

    public function localCloseForContactsAt(): Attribute
    {
        return Attribute::get(fn () => $this->close_for_contacts_at
            ? $this->dateFromApp($this->close_for_contacts_at)
            : null
        );
    }

    public function localOpenForTeachersAt(): Attribute
    {
        return Attribute::get(fn () => $this->open_for_teachers_at
            ? $this->dateFromApp($this->open_for_teachers_at)
            : null
        );
    }

    public function localCloseForTeachersAt(): Attribute
    {
        return Attribute::get(fn () => $this->close_for_teachers_at
            ? $this->dateFromApp($this->close_for_teachers_at)
            : null
        );
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['staff_id']);
    }

    /** @return HasMany<Course, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** @return HasMany<SchoolLanguage, $this> */
    public function languages(): HasMany
    {
        return $this->hasMany(SchoolLanguage::class);
    }

    public function syncFromSis(): static
    {
        $this->tenant->getSisProvider()?->syncSchool($this);

        return $this;
    }

    public function syncStaff(): static
    {
        $this->tenant->getSisProvider()?->syncSchoolStaff($this);

        return $this;
    }

    public function syncStudents(): static
    {
        $this->tenant->getSisProvider()?->syncSchoolStudents($this);

        return $this;
    }

    public function syncCourses(): static
    {
        $this->tenant->getSisProvider()?->syncSchoolCourses($this);

        return $this;
    }

    public function syncSections(): static
    {
        $this->tenant->getSisProvider()?->syncSchoolSections($this);

        return $this;
    }

    public static function current(): static
    {
        return request()->school();
    }

    /** @return array<int, int> */
    public function gradeSelectOptions(): array
    {
        return range((int) $this->low_grade, (int) $this->high_grade);
    }

    public function fullCalendarEventUrl(): string
    {
        return route('schools.event-source', $this);
    }
}
