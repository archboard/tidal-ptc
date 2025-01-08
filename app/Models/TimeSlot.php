<?php

namespace App\Models;

use App\Http\Resources\TimeSlotResource;
use App\Traits\BelongsToSchool;
use App\Traits\BelongsToTenant;
use App\Traits\BelongsToUser;
use GrantHolle\Timezone\Facades\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TimeSlot extends Model
{
    use BelongsToSchool;
    use BelongsToTenant;
    use BelongsToUser;
    use BelongsToUser;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'reserved_at' => 'datetime',
        'is_online' => 'boolean',
        'requested_online' => 'boolean',
        'contact_can_book' => 'boolean',
        'allow_translator_requests' => 'boolean',
        'allow_online_meetings' => 'boolean',
    ];

    public function scopeExpired(Builder $builder): void
    {
        $builder->where('starts_at', '<', now());
    }

    public function scopeNotExpired(Builder $builder): void
    {
        $builder->where('starts_at', '>', now());
    }

    public function scopeNotReserved(Builder $builder): void
    {
        $builder->whereNull('student_id');
    }

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

    public function localStartsAt(): Attribute
    {
        return Attribute::get(fn () => Timezone::toLocal($this->starts_at));
    }

    public function localEndsAt(): Attribute
    {
        return Attribute::get(fn () => Timezone::toLocal($this->ends_at));
    }

    public function localReservedAt(): Attribute
    {
        return Attribute::get(
            fn () => $this->reserved_at ? Timezone::toLocal($this->reserved_at) : null
        );
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

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

    public function toFullCalendar(): array
    {
        return [
            'id' => $this->id ?? Str::random(5),
            'groupId' => $this->batch_id,
            'title' => '',
            'allDay' => false,
            'start' => $this->starts_at->toIso8601String(),
            'end' => $this->ends_at->toIso8601String(),
            'overlap' => false,
            'extendedProps' => (new TimeSlotResource($this))
                ->response()
                ->getData(true),
        ];
    }

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
