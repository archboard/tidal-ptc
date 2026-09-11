<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Notifications\ReminderNotification;
use App\Notifications\TimeSlotNotification;
use Illuminate\Notifications\Events\NotificationSent;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;

        $properties = match (true) {
            $notification instanceof TimeSlotNotification => ['event' => $notification->event->value, 'time_slot_id' => $notification->slot->id],
            $notification instanceof ReminderNotification => ['event' => 'slot_reminder', 'time_slot_ids' => array_map(fn ($slot) => $slot->id, $notification->slots)],
            default => null,
        };

        if ($properties === null) {
            return;
        }

        ActivityEvent::notification_sent->log($event->notifiable, [
            ...$properties,
            'channel' => $event->channel,
        ]);
    }
}
