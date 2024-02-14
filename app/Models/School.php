<?php

namespace App\Models;

use App\Models\Contracts\ExistsInSis;
use App\Traits\BelongsToTenant;
use App\Traits\HasTimezone;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSchool
 */
class School extends Model implements ExistsInSis
{
    use BelongsToTenant;
    use HasFactory;
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['staff_id']);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class)
            ->withPivot(['request_max', 'overlap_max']);
    }

    public function syncFromSis(): static
    {
        $provider = $this->tenant->getSisProvider();

        return $provider->syncSchool($this);
    }

    public function syncStaff(): static
    {
        $provider = $this->tenant->getSisProvider();
        $provider->syncSchoolStaff($this);

        return $this;
    }

    public function syncStudents(): static
    {
        $provider = $this->tenant->getSisProvider();
        $provider->syncSchoolStudents($this);

        return $this;
    }

    public function syncCourses(): static
    {
        $provider = $this->tenant->getSisProvider();
        $provider->syncSchoolCourses($this);

        return $this;
    }

    public function syncSections(): static
    {
        $provider = $this->tenant->getSisProvider();
        $provider->syncSchoolSections($this);

        return $this;
    }

    public static function current(): static
    {
        return request()->school();
    }

    public function gradeSelectOptions(): array
    {
        return range($this->low_grade, $this->high_grade);
    }
}
