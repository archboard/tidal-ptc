<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use App\Traits\HasTimeSlots;
use App\Traits\ScopedToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $school_id
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\School $school
 * @property-read \App\Models\Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
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

    public function fullCalendarEventUrl(): string
    {
        return route('batches.event-source', $this);
    }
}
