<?php

namespace App\Listeners;

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

        activity()
            ->performedOn($event->notifiable)
            ->event('notification_sent')
            ->withProperties([...$properties, 'channel' => $event->channel])
            ->log('notification_sent');
    }
}
