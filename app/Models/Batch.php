<?php

namespace App\Models;

use App\Enums\ActivityEvent;
use App\Enums\NotificationEvent;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use App\Traits\HasTimeSlots;
use App\Traits\ScopedToSchool;
use Carbon\CarbonImmutable;
use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int|null $user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read School $school
 * @property-read Tenant $tenant
 * @property-read Collection<int, TimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 * @property-read int|null $distinct_times_count
 * @property-read User|null $user
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\BatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereSchoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Batch extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;

    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    use HasTimeSlots;
    use ScopedToSchool;

    protected $guarded = [];

    /** @return HasManyThrough<User, BatchUser, $this> */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            BatchUser::class,
            'batch_id',
            'id',
            'id',
            'user_id'
        );
    }

    /**
     * Update the slots in the window given by `starts_at`/`ends_at`, log it,
     * and notify the contacts of reserved slots that changed notifiably.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateTimeSlotsAndNotify(array $attributes): void
    {
        $affected = $this->timeSlots()
            ->where('starts_at', $attributes['starts_at'])
            ->where('ends_at', $attributes['ends_at'])
            ->reserved()
            ->get()
            ->filter(fn (TimeSlot $slot) => $slot->fill($attributes)->isDirty(TimeSlot::NOTIFIABLE_CHANGES));

        $this->updateTimeSlots($attributes);
        ActivityEvent::batch_updated->log($this, $attributes);

        $affected->each(function (TimeSlot $slot) {
            $slot->refresh()->notifyReservation(NotificationEvent::slot_updated);
            $slot->update(['contact_reminded_at' => null, 'staff_reminded_at' => null]);
        });
    }

    public function fullCalendarEventUrl(): string
    {
        return route('batches.event-source', $this);
    }
}
