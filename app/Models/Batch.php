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

class Batch extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;
    use HasFactory;
    use HasTimeSlots;
    use ScopedToSchool;

    protected $guarded = [];

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
