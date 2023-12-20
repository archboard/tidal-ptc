<?php

namespace App\Enums;

use App\Enums\Traits\HasOptions;

enum NotificationEvent: string
{
    use HasOptions;

    case slot_booked = 'slot_booked';
    case slot_cancelled = 'slot_cancelled';
    case slot_rescheduled = 'slot_rescheduled';
    case slot_updated = 'slot_updated';
    case slot_reminder = 'slot_reminder';

    public function label(): string
    {
        return match ($this) {
            self::slot_booked => __('Reservation booked'),
            self::slot_cancelled => __('Reservation cancelled'),
            self::slot_rescheduled => __('Reservation rescheduled'),
            self::slot_updated => __('Reservation updated'),
            self::slot_reminder => __('Reservation reminder'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::slot_booked => __('Sent when a reservation is booked'),
            self::slot_cancelled => __('Sent when a reservation is cancelled'),
            self::slot_rescheduled => __('Sent when a reservation is rescheduled'),
            self::slot_reminder => __('Sent as a reminder for a reservation'),
            self::slot_updated => __('Sent when a reservation has updated details'),
        };
    }

    public function getUserTypes(): array
    {
        return match ($this) {
            self::slot_booked => [UserType::staff],
            default => [UserType::staff, UserType::guardian],
        };
    }
}
