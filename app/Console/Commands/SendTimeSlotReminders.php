<?php

namespace App\Console\Commands;

use App\Data\TimeSlotSnapshot;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendTimeSlotReminders extends Command
{
    protected $signature = 'ptc:send-reminders';

    protected $description = 'Email each user a digest of their upcoming conferences, at their configured lead time';

    /**
     * A user is due when a reservation they take part in starts within their lead time and has
     * not been included in a reminder to them yet. The digest lists all of their upcoming
     * reservations and stamps each one, so a rescheduled or newly booked slot reminds again.
     */
    public function handle(): int
    {
        $sent = 0;

        foreach (Tenant::cursor() as $tenant) {
            $sent += $tenant->execute(fn () => $this->remindUsers());
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    protected function remindUsers(): int
    {
        $sent = 0;
        $slotsByUser = TimeSlot::query()
            ->reserved()
            ->notExpired()
            ->with('user', 'student', 'reservedBy')
            ->orderBy('starts_at')
            ->get()
            ->reduce(function (array $carry, TimeSlot $slot) {
                foreach (array_filter([$slot->user, $slot->reservedBy]) as $participant) {
                    $carry[$participant->id] ??= ['user' => $participant, 'slots' => collect()];
                    $carry[$participant->id]['slots']->push($slot);
                }

                return $carry;
            }, []);

        foreach ($slotsByUser as ['user' => $user, 'slots' => $slots]) {
            /** @var User $user */
            /** @var Collection<int, TimeSlot> $slots */
            $due = $slots->contains(fn (TimeSlot $slot) => $slot->starts_at->lte(now()->addHours($user->reminderHours()))
                && $slot->{$slot->reminderColumnFor($user)} === null);

            if (! $due) {
                continue;
            }

            $user->notify(new ReminderNotification($slots->map(fn (TimeSlot $slot) => TimeSlotSnapshot::fromTimeSlot($slot))->all()));
            $slots->each(fn (TimeSlot $slot) => $slot->update([$slot->reminderColumnFor($user) => now()]));
            $sent++;
        }

        return $sent;
    }
}
