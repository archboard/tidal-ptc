<?php

use App\Console\Commands\SendTimeSlotReminders;
use App\Data\TimeSlotSnapshot;
use App\Enums\Language;
use App\Enums\NotificationEvent;
use App\Enums\Permission;
use App\Enums\UserType;
use App\Models\TimeSlot;
use App\Models\Translator;
use App\Notifications\ReminderNotification;
use App\Notifications\TimeSlotNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    logIn()->setSchool();
    $this->school->update(['booking_buffer_hours' => 2, 'open_for_teachers_at' => now()->subDay()]);

    $this->teacher = seedUser(['user_type' => UserType::staff]);
    $this->student = seedSection($this->teacher)->students->first();
    $this->guardian = seedGuardian($this->student);
    $this->slot = seedBookableSlot($this->teacher);
});

function reserve(TimeSlot $slot): TimeSlot
{
    $slot->update(['student_id' => test()->student->id, 'reserved_by' => test()->guardian->id, 'reserved_at' => now()]);

    return $slot;
}

function assertNotified(NotificationEvent $event, ...$users): void
{
    foreach ($users as $user) {
        Notification::assertSentTo($user, TimeSlotNotification::class, fn (TimeSlotNotification $n) => $n->event === $event);
    }
}

it('notifies both parties when booked', function () {
    $this->actingAs($this->guardian)
        ->postJson(route('reservations.store', $this->slot), ['student_id' => $this->student->id])
        ->assertOk();

    assertNotified(NotificationEvent::slot_booked, $this->teacher, $this->guardian);
});

it('notifies both parties when cancelled by the teacher', function () {
    reserve($this->slot);

    $this->actingAs($this->teacher)
        ->deleteJson(route('reservations.destroy', $this->slot))
        ->assertOk();

    assertNotified(NotificationEvent::slot_cancelled, $this->teacher, $this->guardian);
    Notification::assertSentTo($this->guardian, TimeSlotNotification::class, fn (TimeSlotNotification $n) => $n->slot->student === $this->student->name);
});

it('notifies when rescheduled with the previous time', function () {
    reserve($this->slot);
    $target = seedBookableSlot($this->teacher, ['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);

    $this->actingAs($this->guardian)
        ->putJson(route('reservations.update', $this->slot), ['time_slot_id' => $target->id])
        ->assertOk();

    Notification::assertSentTo($this->teacher, TimeSlotNotification::class, fn (TimeSlotNotification $n) => $n->event === NotificationEvent::slot_rescheduled
        && $n->slot->previousStartsAt?->equalTo($this->slot->starts_at)
        && $n->slot->id === $target->id);
});

it('notifies when a reserved slot is updated', function () {
    reserve($this->slot);
    $data = makeTimeSlotRequest([
        'starts_at' => $this->slot->starts_at->toIso8601ZuluString(),
        'ends_at' => $this->slot->ends_at->toIso8601ZuluString(),
        'location' => 'Room 9',
    ]);

    $this->actingAs($this->teacher)
        ->putJson(route('time-slots.update', $this->slot), $data)
        ->assertOk();

    assertNotified(NotificationEvent::slot_updated, $this->guardian);
});

it('does not notify when a reserved slot update changes nothing relevant', function () {
    reserve($this->slot);
    $data = [
        ...makeTimeSlotRequest(),
        'starts_at' => $this->slot->starts_at->toIso8601ZuluString(),
        'ends_at' => $this->slot->ends_at->toIso8601ZuluString(),
        'location' => $this->slot->location,
        'meeting_url' => $this->slot->meeting_url,
        'is_online' => $this->slot->is_online,
        'teacher_notes' => $this->slot->teacher_notes,
        'contact_can_book' => false,
    ];

    $this->actingAs($this->teacher)
        ->putJson(route('time-slots.update', $this->slot), $data)
        ->assertOk();

    Notification::assertNothingSent();
});

it('notifies reserved slots updated through a batch', function () {
    $batch = seedBatch();
    $slot = reserve($batch->timeSlots()->first());
    $data = makeTimeSlotRequest(['batch_id' => $batch->id, 'update_batch' => true, 'location' => 'Gym']);

    $this->givePermission(Permission::update, TimeSlot::class)
        ->putJson(route('time-slots.update', $slot), $data)
        ->assertOk();

    assertNotified(NotificationEvent::slot_updated, $this->guardian);
});

it('blocks deleting a reserved slot without permission and notifies when an admin deletes', function () {
    reserve($this->slot);

    $this->actingAs($this->teacher)
        ->deleteJson(route('time-slots.destroy', $this->slot))
        ->assertForbidden();

    $this->actingAs($this->user);
    fullPermissions()
        ->deleteJson(route('time-slots.destroy', $this->slot))
        ->assertOk();

    assertNotified(NotificationEvent::slot_cancelled, $this->teacher, $this->guardian);
    expect(TimeSlot::find($this->slot->id))->toBeNull();
});

it('respects opted-out preferences', function () {
    $this->guardian->update(['notification_config' => ['slot_booked' => false]]);
    $notification = new TimeSlotNotification(NotificationEvent::slot_booked, TimeSlotSnapshot::fromTimeSlot(reserve($this->slot)));

    expect($notification->via($this->guardian))->toBe([])
        ->and($notification->via($this->teacher))->toBe(['mail']);
});

it('renders times in the recipient timezone', function () {
    $this->guardian->update(['timezone' => 'Asia/Tokyo', 'is_24h' => true]);
    reserve($this->slot)->load('user', 'student', 'reservedBy');
    $starts = $this->slot->starts_at->setTimezone('Asia/Tokyo');

    $this->slot->notifyReservation(NotificationEvent::slot_booked);

    Notification::assertSentTo($this->guardian, TimeSlotNotification::class, function (TimeSlotNotification $n) use ($starts) {
        $lines = collect($n->toMail($this->guardian)->introLines)->implode("\n");

        return str_contains($lines, $starts->isoFormat('HH:mm')) && str_contains($lines, 'Asia/Tokyo');
    });
});

it('sends each user one digest of upcoming conferences at their lead time', function () {
    $first = reserve($this->slot->fill(['starts_at' => now()->addHours(24), 'ends_at' => now()->addHours(24)->addMinutes(15)]));
    $later = reserve(seedBookableSlot($this->teacher, ['starts_at' => now()->addHours(30), 'ends_at' => now()->addHours(30)->addMinutes(15)]));
    seedBookableSlot($this->teacher, ['starts_at' => now()->addHours(24), 'ends_at' => now()->addHours(24)->addMinutes(15)]);

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();

    foreach ([$this->teacher, $this->guardian] as $user) {
        Notification::assertSentTo($user, ReminderNotification::class, fn (ReminderNotification $n) => collect($n->slots)->pluck('id')->all() === [$first->id, $later->id]);
    }
    Notification::assertCount(2);
    expect($first->refresh()->contact_reminded_at)->not->toBeNull()
        ->and($first->staff_reminded_at)->not->toBeNull()
        ->and($later->refresh()->contact_reminded_at)->not->toBeNull();

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    Notification::assertCount(2);
});

it('respects a custom reminder lead time', function () {
    reserve($this->slot->fill(['starts_at' => now()->addHours(30), 'ends_at' => now()->addHours(30)->addMinutes(15)]));
    $this->guardian->update(['notification_config' => ['reminder_hours' => 48]]);

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();

    Notification::assertSentTo($this->guardian, ReminderNotification::class);
    Notification::assertNotSentTo($this->teacher, ReminderNotification::class);
});

it('reminds again when a new reservation is booked after the last reminder', function () {
    reserve($this->slot->fill(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(20)->addMinutes(15)]))
        ->update(['contact_reminded_at' => now()->subHour(), 'staff_reminded_at' => now()->subHour()]);

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    Notification::assertNothingSent();

    reserve(seedBookableSlot($this->teacher, ['starts_at' => now()->addHours(22), 'ends_at' => now()->addHours(22)->addMinutes(15)]));

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    Notification::assertSentTo($this->guardian, ReminderNotification::class, fn (ReminderNotification $n) => count($n->slots) === 2);
});

it('reminds again after a reserved slot changes', function () {
    reserve($this->slot->fill(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(20)->addMinutes(15)]))
        ->update(['contact_reminded_at' => now()->subHour(), 'staff_reminded_at' => now()->subHour()]);
    $data = makeTimeSlotRequest([
        'starts_at' => $this->slot->starts_at->toIso8601ZuluString(),
        'ends_at' => $this->slot->ends_at->toIso8601ZuluString(),
        'location' => 'Library',
    ]);

    $this->actingAs($this->teacher)->putJson(route('time-slots.update', $this->slot), $data)->assertOk();

    expect($this->slot->refresh()->contact_reminded_at)->toBeNull();
    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    Notification::assertSentTo($this->guardian, ReminderNotification::class);
});

it('does not remind again when nothing has changed', function () {
    reserve($this->slot->fill(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(20)->addMinutes(15)]));

    $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    Notification::assertSentToTimes($this->guardian, ReminderNotification::class, 1);
    Notification::assertSentToTimes($this->teacher, ReminderNotification::class, 1);

    foreach (range(1, 3) as $hour) {
        $this->travel($hour)->hours();
        $this->artisan(SendTimeSlotReminders::class)->assertSuccessful();
    }

    Notification::assertSentToTimes($this->guardian, ReminderNotification::class, 1);
    Notification::assertSentToTimes($this->teacher, ReminderNotification::class, 1);
});

it('validates the reminder lead time setting', function () {
    $this->put('/settings/personal/notifications', ['reminder_hours' => 0])
        ->assertSessionHasErrors('reminder_hours');

    $this->put('/settings/personal/notifications', ['reminder_hours' => 48])
        ->assertSessionHasNoErrors();

    expect($this->user->refresh()->reminderHours())->toBe(48);
});

it('names the assigned translator in the email', function () {
    $translator = Translator::factory()->create(['first_name' => 'Yuki', 'last_name' => 'Sato']);
    reserve($this->slot)->update(['language' => Language::JAPANESE, 'translator_id' => $translator->id]);
    $this->slot->refresh()->load('user', 'student', 'reservedBy', 'translator');

    $lines = collect((new TimeSlotNotification(NotificationEvent::slot_booked, TimeSlotSnapshot::fromTimeSlot($this->slot)))->toMail($this->guardian)->introLines)->implode("\n");

    expect($lines)->toContain('Yuki Sato')->toContain('Japanese');
});
