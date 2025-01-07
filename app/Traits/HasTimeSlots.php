<?php

namespace App\Traits;

use App\Models\TimeSlot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait HasTimeSlots
{
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
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

    public function fullCalendarEventUrl(): string
    {
        return '';
    }

    public function fullCalendarEventSourceId(): string
    {
        return $this->getMorphClass().'_'.$this->id;
    }

    public function fullCalendarEventSource(): array
    {
        return [
            'id' => $this->fullCalendarEventSourceId(),
            'url' => $this->fullCalendarEventUrl(),
        ];
    }

    public function getTimeSlotsFromFullCalendarRequest(Request $request): array
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = CarbonImmutable::parse($request->input('start'))
            ->setTimezone(config('app.timezone'));
        $end = CarbonImmutable::parse($request->input('end'))
            ->setTimezone(config('app.timezone'));

        return $this->fullCalendarEvents($start, $end);
    }
}
