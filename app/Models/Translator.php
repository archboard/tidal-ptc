<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use App\Enums\Language;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\ScopedToSchool;
use Carbon\CarbonImmutable;
use Database\Factories\TranslatorFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A person the school can assign to interpret a conference. Translators do not log in;
 * their schedule is exported for them by an admin.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $notes
 * @property \Illuminate\Support\Collection<int, Language> $languages
 * @property bool $active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, TimeSlot> $assignments
 * @property-read School $school
 * @property-read Tenant $tenant
 *
 * @method static Builder<static>|Translator active()
 * @method static Builder<static>|Translator speaks(Language $language)
 * @method static TranslatorFactory factory($count = null, $state = [])
 *
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read int|null $assignments_count
 *
 * @method static Builder<static>|Translator newModelQuery()
 * @method static Builder<static>|Translator newQuery()
 * @method static Builder<static>|Translator onlyTrashed()
 * @method static Builder<static>|Translator query()
 * @method static Builder<static>|Translator whereActive($value)
 * @method static Builder<static>|Translator whereCreatedAt($value)
 * @method static Builder<static>|Translator whereDeletedAt($value)
 * @method static Builder<static>|Translator whereEmail($value)
 * @method static Builder<static>|Translator whereId($value)
 * @method static Builder<static>|Translator whereLanguages($value)
 * @method static Builder<static>|Translator whereName($value)
 * @method static Builder<static>|Translator whereNotes($value)
 * @method static Builder<static>|Translator wherePhone($value)
 * @method static Builder<static>|Translator whereSchoolId($value)
 * @method static Builder<static>|Translator whereTenantId($value)
 * @method static Builder<static>|Translator whereUpdatedAt($value)
 * @method static Builder<static>|Translator withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Translator withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Translator extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;

    /** @use HasFactory<TranslatorFactory> */
    use HasFactory;

    use LogsActivity;
    use ScopedToSchool;
    use SoftDeletes;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'languages' => AsEnumCollection::of(Language::class),
            'active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event) => ActivityEvent::from($event)->description());
    }

    /** @param Builder<static> $builder */
    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('active', true);
    }

    /** @param Builder<static> $builder */
    #[Scope]
    protected function speaks(Builder $builder, Language $language): void
    {
        $builder->whereJsonContains('languages', $language->value);
    }

    /** @return HasMany<TimeSlot, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function speaksLanguage(?Language $language): bool
    {
        return $language !== null && $this->languages->contains($language);
    }

    /** No other assignment overlapping the given slot. */
    public function isAvailableFor(TimeSlot $timeSlot): bool
    {
        return ! $this->assignments()
            ->whereKeyNot($timeSlot->id)
            ->whereOverlaps($timeSlot->starts_at->toDateTimeString(), $timeSlot->ends_at->toDateTimeString())
            ->exists();
    }
}
