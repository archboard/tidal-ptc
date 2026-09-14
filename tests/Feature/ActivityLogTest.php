<?php

use App\Enums\UserType;
use App\Models\Activity;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    logIn()->setSchool();
    $this->school->update(['booking_buffer_hours' => 2, 'open_for_teachers_at' => now()->subDay()]);

    $this->teacher = seedUser(['user_type' => UserType::staff]);
    $this->student = seedSection($this->teacher)->students->first();
    $this->guardian = seedGuardian($this->student);
    $this->slot = seedBookableSlot($this->teacher);
});

it('logs a booking with causer, subject and details', function () {
    $this->actingAs($this->guardian)
        ->postJson(route('reservations.store', $this->slot), ['student_id' => $this->student->id])
        ->assertOk();

    $activity = Activity::where('event', 'reservation_booked')->sole();
    expect($activity->causer_id)->toBe($this->guardian->id)
        ->and($activity->subject_id)->toBe($this->slot->id)
        ->and($activity->subject_type)->toBe($this->slot->getMorphClass())
        ->and($activity->tenant_id)->toBe($this->tenant->id)
        ->and($activity->school_id)->toBe($this->school->id)
        ->and($activity->getProperty('student'))->toBe($this->student->name);
});

it('logs cancellations and reschedules', function () {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'reserved_at' => now()]);
    $target = seedBookableSlot($this->teacher, ['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);

    $this->actingAs($this->guardian)
        ->putJson(route('reservations.update', $this->slot), ['time_slot_id' => $target->id])
        ->assertOk();
    $this->deleteJson(route('reservations.destroy', $target))->assertOk();

    $rescheduled = Activity::where('event', 'reservation_rescheduled')->sole();
    expect($rescheduled->subject_id)->toBe($target->id)
        ->and($rescheduled->getProperty('from_time_slot_id'))->toBe($this->slot->id)
        ->and(Activity::where('event', 'reservation_cancelled')->where('subject_id', $target->id)->exists())->toBeTrue();
});

it('logs slot changes with old and new values but not reservation columns', function () {
    $this->actingAs($this->teacher)
        ->putJson(route('time-slots.update', $this->slot), makeTimeSlotRequest([
            'starts_at' => $this->slot->starts_at->toIso8601ZuluString(),
            'ends_at' => $this->slot->ends_at->toIso8601ZuluString(),
            'location' => 'Room 42',
        ]))
        ->assertOk();

    $activity = Activity::where('event', 'updated')->where('subject_id', $this->slot->id)->sole();
    expect($activity->attribute_changes['attributes']['location'])->toBe('Room 42')
        ->and($activity->attribute_changes['old']['location'])->toBe($this->slot->location);

    $this->slot->refresh()->update(['student_id' => Student::factory()->create()->id]);
    expect(Activity::where('subject_id', $this->slot->id)->where('event', 'updated')->count())->toBe(1);
});

it('logs school settings changes', function () {
    $this->school->update(['booking_buffer_hours' => 5]);

    $activity = Activity::where('subject_type', $this->school->getMorphClass())->latest('id')->first();
    expect($activity->attribute_changes['attributes']['booking_buffer_hours'])->toBe(5)
        ->and($activity->school_id)->toBe($this->school->id);
});

it('logs tenant changes without secrets', function () {
    $this->tenant->update(['name' => 'Renamed', 'smtp_config' => collect(['password' => 'hunter2'])]);

    $activity = Activity::where('subject_type', $this->tenant->getMorphClass())->latest('id')->first();
    expect($activity->attribute_changes['attributes'])->toHaveKey('name')
        ->and(json_encode($activity->attribute_changes))->not->toContain('hunter2');
});

it('logs each notification sent', function () {
    $this->actingAs($this->guardian)
        ->postJson(route('reservations.store', $this->slot), ['student_id' => $this->student->id])
        ->assertOk();

    $sent = Activity::where('event', 'notification_sent')->get();
    expect($sent->pluck('subject_id')->sort()->values()->all())->toBe(collect([$this->teacher->id, $this->guardian->id])->sort()->values()->all())
        ->and($sent->first()->getProperty('event'))->toBe('slot_booked')
        ->and($sent->first()->getProperty('time_slot_id'))->toBe($this->slot->id);
});

it('scopes activity to the current tenant', function () {
    Activity::withoutGlobalScopes()->create(['description' => 'elsewhere', 'tenant_id' => $this->tenant->id + 1]);

    expect(Activity::where('description', 'elsewhere')->exists())->toBeFalse()
        ->and(Activity::withoutGlobalScopes()->where('description', 'elsewhere')->exists())->toBeTrue();
});
