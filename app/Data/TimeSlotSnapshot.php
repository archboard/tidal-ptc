<?php

namespace App\Data;

use App\Models\TimeSlot;
use Carbon\CarbonImmutable;

/**
 * Immutable copy of the reservation details at the moment an event happens,
 * so queued notifications are unaffected by the slot changing afterwards.
 */
final readonly class TimeSlotSnapshot
{
    public function __construct(
        public int $id,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public string $teacher,
        public ?string $student,
        public ?string $contact,
        public ?string $location,
        public ?string $meetingUrl,
        public bool $isOnline,
        public ?string $language,
        public ?CarbonImmutable $previousStartsAt = null,
        public ?CarbonImmutable $previousEndsAt = null,
    ) {}

    public static function fromTimeSlot(TimeSlot $timeSlot, ?TimeSlot $previous = null): self
    {
        return new self(
            id: $timeSlot->id,
            startsAt: $timeSlot->starts_at,
            endsAt: $timeSlot->ends_at,
            teacher: $timeSlot->user->name,
            student: $timeSlot->student?->name,
            contact: $timeSlot->reservedBy?->name,
            location: $timeSlot->location,
            meetingUrl: $timeSlot->meeting_url ?: $timeSlot->user->meeting_url,
            isOnline: $timeSlot->is_online || $timeSlot->requested_online,
            language: $timeSlot->language?->name(),
            previousStartsAt: $previous?->starts_at,
            previousEndsAt: $previous?->ends_at,
        );
    }
}
