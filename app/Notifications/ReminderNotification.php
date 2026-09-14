<?php

namespace App\Notifications;

use App\Data\TimeSlotSnapshot;
use App\Enums\NotificationEvent;
use App\Models\User;
use App\Notifications\Traits\FormatsSubject;
use App\Notifications\Traits\FormatsTimeSlotRange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Digest of every upcoming conference a user takes part in. */
class ReminderNotification extends Notification implements ShouldQueue
{
    use FormatsSubject;
    use FormatsTimeSlotRange;
    use Queueable;

    /** @param array<int, TimeSlotSnapshot> $slots */
    public function __construct(public array $slots) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return $notifiable->email && $notifiable->wantsNotification(NotificationEvent::slot_reminder)
            ? ['mail']
            : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->formatSubject(NotificationEvent::slot_reminder->name()))
            ->line(__('Here are your upcoming conferences:'));

        foreach ($this->slots as $slot) {
            $where = match (true) {
                $slot->isOnline => __('Online').($slot->meetingUrl ? " – {$slot->meetingUrl}" : ''),
                (bool) $slot->location => $slot->location,
                default => null,
            };

            $message->line(
                '- '.$this->formatRange($notifiable, $slot->startsAt, $slot->endsAt)
                .': '.__(':student with :teacher', ['student' => $slot->student, 'teacher' => $slot->teacher])
                .($where ? " ({$where})" : '')
            );
        }

        return $message->action(__('Open dashboard'), route('home'));
    }
}
