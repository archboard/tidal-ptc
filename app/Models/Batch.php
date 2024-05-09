<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Batch extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;

    protected $guarded = [];

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

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

    public function fullCalendarEvents(?CarbonImmutable $start = null, ?CarbonImmutable $end = null): array
    {
        return $this->timeSlots()
            ->select([DB::raw('distinct on (starts_at) *')])
            ->when($start, fn ($query, $start) => $query->where('starts_at', '>=', $start))
            ->when($end, fn ($query, $end) => $query->where('ends_at', '<=', $end))
            ->get()
            ->map(fn (TimeSlot $timeSlot) => $timeSlot->toFullCalendar())
            ->toArray();
    }

    public function updateTimeSlots(array $attributes): static
    {
        $this->timeSlots()
            ->where('starts_at', $attributes['starts_at'])
            ->where('ends_at', $attributes['ends_at'])
            ->update($attributes);

        return $this;
    }

    public function deleteTimeSlots(string|CarbonImmutable $start, string|CarbonImmutable $end): static
    {
        $this->timeSlots()
            ->where('starts_at', $start)
            ->where('ends_at', $end)
            ->whereNull('student_id')
            ->delete();

        return $this;
    }

    public function fullCalendarEventSource(): array
    {
        return [
            'id' => (string) $this->id,
            'url' => route('batches.event-source', $this),
        ];
    }
}
