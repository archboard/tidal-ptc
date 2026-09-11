<?php

namespace App\Notifications;

use App\Data\TimeSlotSnapshot;
use App\Enums\NotificationEvent;
use App\Models\User;
use App\Notifications\Traits\FormatsSubject;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ponytail: default QUEUE_CONNECTION=sync; a real driver keeps tenant SMTP via the
 * multitenancy queue tasks already configured in config/multitenancy.php.
 */
class TimeSlotNotification extends Notification implements ShouldQueue
{
    use FormatsSubject;
    use Queueable;

    public function __construct(public NotificationEvent $event, public TimeSlotSnapshot $slot) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return $notifiable->email && $notifiable->wantsNotification($this->event)
            ? ['mail']
            : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $when = $this->formatRange($notifiable, $this->slot->startsAt, $this->slot->endsAt);
        $with = __(':student with :teacher', ['student' => $this->slot->student, 'teacher' => $this->slot->teacher]);

        $message = (new MailMessage)
            ->subject($this->formatSubject($this->event->name()))
            ->line(match ($this->event) {
                NotificationEvent::slot_booked => __('A conference has been booked for :with.', ['with' => $with]),
                NotificationEvent::slot_cancelled => __('The conference for :with has been cancelled.', ['with' => $with]),
                NotificationEvent::slot_rescheduled => __('The conference for :with has been rescheduled.', ['with' => $with]),
                NotificationEvent::slot_updated => __('The details of the conference for :with have changed.', ['with' => $with]),
                NotificationEvent::slot_reminder => __('This is a reminder of your upcoming conference for :with.', ['with' => $with]),
            });

        if ($this->slot->previousStartsAt && $this->slot->previousEndsAt) {
            $message->line(__('Previously: :when', [
                'when' => $this->formatRange($notifiable, $this->slot->previousStartsAt, $this->slot->previousEndsAt),
            ]));
        }

        $message->line(__('When: :when', ['when' => $when]));

        if ($this->slot->isOnline) {
            $message->line(__('Where: Online'));
            if ($this->slot->meetingUrl) {
                $message->line($this->slot->meetingUrl);
            }
        } elseif ($this->slot->location) {
            $message->line(__('Where: :location', ['location' => $this->slot->location]));
        }

        if ($this->slot->language) {
            $message->line(__('Translator requested: :language', ['language' => $this->slot->language]));
        }

        return $message->action(__('Open dashboard'), route('home'));
    }

    protected function formatRange(User $notifiable, CarbonImmutable $start, CarbonImmutable $end): string
    {
        $timeFormat = $notifiable->is_24h ? 'HH:mm' : 'h:mm A';
        $starts = $notifiable->dateFromApp($start);
        $ends = $notifiable->dateFromApp($end);

        return $starts->isoFormat('dddd, LL')
            .' '.$starts->isoFormat($timeFormat)
            .' – '.$ends->isoFormat($timeFormat)
            .' ('.$starts->tzName.')';
    }
}
