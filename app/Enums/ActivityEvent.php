<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * Every activity log entry's `event`. Descriptions are stored in English and translated when
 * displayed (see ActivityResource), so the log stays locale-independent.
 */
enum ActivityEvent: string
{
    case created = 'created';
    case updated = 'updated';
    case deleted = 'deleted';
    case reservation_booked = 'reservation_booked';
    case reservation_rescheduled = 'reservation_rescheduled';
    case reservation_cancelled = 'reservation_cancelled';
    case batch_created = 'batch_created';
    case batch_updated = 'batch_updated';
    case smtp_updated = 'smtp_updated';
    case notification_sent = 'notification_sent';
    case translator_assigned = 'translator_assigned';
    case translator_unassigned = 'translator_unassigned';

    /** English description; placeholders are filled from the activity's properties at display time. */
    public function description(): string
    {
        return match ($this) {
            // __('Created')
            self::created => 'Created',
            // __('Updated')
            self::updated => 'Updated',
            // __('Deleted')
            self::deleted => 'Deleted',
            // __('Conference booked')
            self::reservation_booked => 'Conference booked',
            // __('Conference rescheduled')
            self::reservation_rescheduled => 'Conference rescheduled',
            // __('Conference cancelled')
            self::reservation_cancelled => 'Conference cancelled',
            // __('Time slots created for :count teachers')
            self::batch_created => 'Time slots created for :count teachers',
            // __('Batch time slots updated')
            self::batch_updated => 'Batch time slots updated',
            // __('SMTP settings updated')
            self::smtp_updated => 'SMTP settings updated',
            // __(':notification email sent')
            self::notification_sent => ':notification email sent',
            // __('Translator :translator assigned')
            self::translator_assigned => 'Translator :translator assigned',
            // __('Translator :translator unassigned')
            self::translator_unassigned => 'Translator :translator unassigned',
        };
    }

    public function logger(?Model $subject = null): ActivityLogger
    {
        $logger = activity()->event($this->value);

        return $subject ? $logger->performedOn($subject) : $logger;
    }

    /** @param array<string, mixed> $properties */
    public function log(?Model $subject = null, array $properties = []): void
    {
        $this->logger($subject)
            ->withProperties($properties)
            ->log($this->description());
    }
}
