<?php

namespace App\Models;

use App\Data\TimeSlotSnapshot;
use App\Enums\Language;
use App\Enums\NotificationEvent;
use App\Http\Resources\TimeSlotResource;
use App\Notifications\TimeSlotNotification;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Carbon\CarbonImmutable;
use Database\Factories\TimeSlotFactory;
use GrantHolle\Timezone\Facades\Timezone;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int $user_id
 * @property int|null $student_id
 * @property int|null $batch_id
 * @property int|null $reserved_by
 * @property int|null $created_by
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable|null $reserved_at
 * @property CarbonImmutable|null $contact_reminded_at
 * @property CarbonImmutable|null $staff_reminded_at
 * @property string|null $teacher_notes
 * @property string|null $contact_notes
 * @property string|null $location
 * @property string|null $meeting_url
 * @property bool $allow_online_meetings
 * @property bool $is_online
 * @property bool $requested_online
 * @property bool $contact_can_book
 * @property bool $allow_translator_requests
 * @property Language|null $language
 * @property string|null $translator_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Batch|null $batch
 * @property-read User|null $createdBy
 * @property-read mixed $local_ends_at
 * @property-read mixed $local_reserved_at
 * @property-read mixed $local_starts_at
 * @property-read User|null $reservedBy
 * @property-read School $school
 * @property-read Student|null $student
 * @property-read Tenant $tenant
 * @property-read User $user
 *
 * @method static Builder<static>|TimeSlot expired()
 * @method static \Database\Factories\TimeSlotFactory factory($count = null, $state = [])
 * @method static Builder<static>|TimeSlot newModelQuery()
 * @method static Builder<static>|TimeSlot newQuery()
 * @method static Builder<static>|TimeSlot notExpired()
 * @method static Builder<static>|TimeSlot notReserved()
 * @method static Builder<static>|TimeSlot reserved()
 * @method static Builder<static>|TimeSlot bookable(School $school)
 * @method static Builder<static>|TimeSlot query()
 * @method static Builder<static>|TimeSlot whereAllowOnlineMeetings($value)
 * @method static Builder<static>|TimeSlot whereAllowTranslatorRequests($value)
 * @method static Builder<static>|TimeSlot whereBatchId($value)
 * @method static Builder<static>|TimeSlot whereContactCanBook($value)
 * @method static Builder<static>|TimeSlot whereContactNotes($value)
 * @method static Builder<static>|TimeSlot whereCreatedAt($value)
 * @method static Builder<static>|TimeSlot whereCreatedBy($value)
 * @method static Builder<static>|TimeSlot whereEndsAt($value)
 * @method static Builder<static>|TimeSlot whereId($value)
 * @method static Builder<static>|TimeSlot whereIsOnline($value)
 * @method static Builder<static>|TimeSlot whereLocation($value)
 * @method static Builder<static>|TimeSlot whereMeetingUrl($value)
 * @method static Builder<static>|TimeSlot whereOverlaps(string $start, string $end)
 * @method static Builder<static>|TimeSlot whereRequestedOnline($value)
 * @method static Builder<static>|TimeSlot whereReservedAt($value)
 * @method static Builder<static>|TimeSlot whereReservedBy($value)
 * @method static Builder<static>|TimeSlot whereSchoolId($value)
 * @method static Builder<static>|TimeSlot whereStartsAt($value)
 * @method static Builder<static>|TimeSlot whereStudentId($value)
 * @method static Builder<static>|TimeSlot whereTeacherNotes($value)
 * @method static Builder<static>|TimeSlot whereTenantId($value)
 * @method static Builder<static>|TimeSlot whereTranslatorNotes($value)
 * @method static Builder<static>|TimeSlot whereUpdatedAt($value)
 * @method static Builder<static>|TimeSlot whereUserId($value)
 * @method static Builder<static>|TimeSlot whereLanguage($value)
 *
 * @mixin \Eloquent
 */
class TimeSlot extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;

    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory;

    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'reserved_at' => 'datetime',
        'contact_reminded_at' => 'datetime',
        'staff_reminded_at' => 'datetime',
        'is_online' => 'boolean',
        'requested_online' => 'boolean',
        'contact_can_book' => 'boolean',
        'allow_translator_requests' => 'boolean',
        'allow_online_meetings' => 'boolean',
        'language' => Language::class,
    ];

    /** @param Builder<static> $builder */
    public function scopeExpired(Builder $builder): void
    {
        $builder->where('starts_at', '<', now());
    }

    /** @param Builder<static> $builder */
    public function scopeNotExpired(Builder $builder): void
    {
        $builder->where('starts_at', '>', now());
    }

    /** @param Builder<static> $builder */
    public function scopeNotReserved(Builder $builder): void
    {
        $builder->whereNull('student_id');
    }

    /**
     * Reservation columns are excluded here; those changes are logged as explicit
     * reservation events by the controllers.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'starts_at', 'ends_at', 'location', 'meeting_url', 'is_online', 'teacher_notes', 'contact_can_book', 'allow_translator_requests', 'allow_online_meetings', 'translator_notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @param Builder<static> $builder */
    #[Scope]
    protected function reserved(Builder $builder): void
    {
        $builder->whereNotNull('student_id');
    }

    /**
     * Slots a contact could reserve right now: open, flagged bookable, and outside the buffer.
     *
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function bookable(Builder $builder, School $school): void
    {
        $builder->notReserved()
            ->where('school_id', $school->id)
            ->where('contact_can_book', true)
            ->where('starts_at', '>', now()->addHours($school->booking_buffer_hours));
    }

    /** @param Builder<static> $builder */
    public function scopeWhereOverlaps(Builder $builder, string $start, string $end): void
    {
        $builder->where(function (Builder $builder) use ($start, $end) {
            $builder->where(function (Builder $builder) use ($start) {
                $builder->where('starts_at', '<=', $start)
                    ->where('ends_at', '>', $start);
            })->orWhere(function (Builder $builder) use ($end) {
                $builder->where('starts_at', '<', $end)
                    ->where('ends_at', '>=', $end);
            })->orWhere(function (Builder $builder) use ($start, $end) {
                $builder->where('starts_at', '>=', $start)
                    ->where('ends_at', '<=', $end);
            });
        });
    }

    /** @return Attribute<string|CarbonImmutable, never> */
    public function localStartsAt(): Attribute
    {
        return Attribute::get(fn () => Timezone::toLocal($this->starts_at));
    }

    /** @return Attribute<string|CarbonImmutable, never> */
    public function localEndsAt(): Attribute
    {
        return Attribute::get(fn () => Timezone::toLocal($this->ends_at));
    }

    /** @return Attribute<string|CarbonImmutable|null, never> */
    public function localReservedAt(): Attribute
    {
        return Attribute::get(
            fn () => $this->reserved_at ? Timezone::toLocal($this->reserved_at) : null
        );
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Name of the reminder stamp column for the given participant. */
    public function reminderColumnFor(User $user): string
    {
        return $user->id === $this->user_id ? 'staff_reminded_at' : 'contact_reminded_at';
    }

    public function isReserved(): bool
    {
        return $this->student_id !== null;
    }

    /** @param Collection<int, TimeSlot> $timeSlots */
    public function overlaps(Collection $timeSlots): bool
    {
        return $timeSlots->contains(
            fn (TimeSlot $timeSlot) => (
                $timeSlot->starts_at <= $this->starts_at &&
                $timeSlot->ends_at > $this->starts_at
            ) ||
            (
                $timeSlot->starts_at < $this->ends_at &&
                $timeSlot->ends_at >= $this->ends_at
            ) ||
            (
                $timeSlot->starts_at >= $this->starts_at &&
                $timeSlot->ends_at <= $this->ends_at
            )
        );
    }

    /**
     * Notify the reserving contact and the slot's staff member. Call before clearing a reservation.
     * Pass the previous slot when rescheduling so the old time is included.
     */
    public function notifyReservation(NotificationEvent $event, ?TimeSlot $previous = null): void
    {
        $notification = new TimeSlotNotification($event, TimeSlotSnapshot::fromTimeSlot($this, $previous));

        $this->user->notify($notification);
        $this->reservedBy?->notify($notification);
    }

    /** @return array<string, mixed> */
    public function toFullCalendar(): array
    {
        return [
            'id' => $this->id ?? Str::random(5),
            'groupId' => $this->batch_id,
            'title' => $this->isReserved() ? $this->student?->name : '',
            'classNames' => $this->isReserved() ? ['reserved'] : [],
            'allDay' => false,
            'start' => $this->starts_at->toIso8601String(),
            'end' => $this->ends_at->toIso8601String(),
            'overlap' => false,
            'extendedProps' => (new TimeSlotResource($this))
                ->response()
                ->getData(true),
        ];
    }

    /**
     * @param  Collection<int, int>  $selection
     * @param  array<string, mixed>  $attributes
     */
    public static function createForSelection(Collection $selection, array $attributes): void
    {
        // Get the selection of those without overlapping existing time slots
        $overlapping = static::query()
            ->whereOverlaps($attributes['starts_at'], $attributes['ends_at'])
            ->whereIn('user_id', $selection)
            ->where('batch_id', '!=', $attributes['batch_id'])
            ->pluck('user_id');

        $sets = $selection->diff($overlapping)
            ->map(fn (int $id) => [
                ...$attributes,
                'user_id' => $id,
            ]);

        static::insert($sets->toArray());
    }
}
