<?php

namespace App\Notifications;

use App\Data\TimeSlotSnapshot;
use App\Enums\NotificationEvent;
use App\Models\User;
use App\Notifications\Traits\FormatsSubject;
use App\Notifications\Traits\FormatsTimeSlotRange;
use Carbon\CarbonInterface;
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

        return $message
            ->action(__('Open dashboard'), route('home'))
            ->attachData($this->toIcs(), 'conferences.ics', ['mime' => 'text/calendar; charset=utf-8; method=PUBLISH']);
    }

    /**
     * One VEVENT per slot, times in UTC so every calendar client converts to its own zone.
     * ponytail: lines aren't folded at 75 octets; the major clients accept long lines.
     */
    public function toIcs(): string
    {
        $escape = fn (string $text): string => str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $text);
        $utc = fn (CarbonInterface $date): string => $date->utc()->format('Ymd\THis\Z');
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//'.$escape(config('app.name')).'//EN', 'METHOD:PUBLISH'];

        foreach ($this->slots as $slot) {
            $where = $slot->isOnline ? ($slot->meetingUrl ?? __('Online')) : $slot->location;

            $lines = [...$lines,
                'BEGIN:VEVENT',
                "UID:time-slot-{$slot->id}@{$host}",
                'DTSTAMP:'.$utc(now()),
                'DTSTART:'.$utc($slot->startsAt),
                'DTEND:'.$utc($slot->endsAt),
                'SUMMARY:'.$escape(__(':student with :teacher', ['student' => $slot->student, 'teacher' => $slot->teacher])),
                ...($where ? ['LOCATION:'.$escape($where)] : []),
                ...($slot->isOnline && $slot->meetingUrl ? ['URL:'.$slot->meetingUrl] : []),
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }
}
