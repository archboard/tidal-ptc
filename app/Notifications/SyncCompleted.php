<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Broadcast-only toast sent to the user who triggered a queued SIS sync.
 * Not queued itself: it's already dispatched from inside a worker.
 */
class SyncCompleted extends Notification
{
    public function __construct(public string $item, public string $level, public string $text) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'item' => $this->item,
            'level' => $this->level,
            'text' => $this->text,
        ]);
    }
}
