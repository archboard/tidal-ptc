<?php

use App\Enums\Language;
use App\Enums\Permission;
use App\Enums\UserType;
use App\Models\Student;
use App\Models\TimeSlot;
use Inertia\Testing\AssertableInertia;
use Silber\Bouncer\BouncerFacade;

beforeEach(function () {
    logIn()->setSchool();

    $this->school->update([
        'timezone' => 'Asia/Shanghai',
        'booking_buffer_hours' => 2,
        'allow_online_meetings' => true,
        'allow_translator_requests' => true,
    ]);

    $this->teacher = seedUser();
    $this->section = seedSection($this->teacher);
    $this->student = $this->section->students->first();
    $this->guardian = seedGuardian($this->student);
    $this->slot = seedBookableSlot($this->teacher, ['allow_online_meetings' => true, 'allow_translator_requests' => true]);
    $this->actingAs($this->guardian);
});

function book(TimeSlot $slot, array $data = [])
{
    return test()->postJson(route('reservations.store', $slot), ['student_id' => test()->student->id, ...$data]);
}

it('books a slot with the basic fields', function () {
    book($this->slot, ['contact_notes' => 'hi', 'requested_online' => true])->assertOk();

    $this->slot->refresh();
    expect($this->slot->student_id)->toBe($this->student->id)
        ->and($this->slot->reserved_by)->toBe($this->guardian->id)
        ->and($this->slot->reserved_at)->not->toBeNull()
        ->and($this->slot->contact_notes)->toBe('hi')
        ->and($this->slot->requested_online)->toBeTrue();
});

it('forbids booking for a student who is not a contact', function () {
    $other = Student::factory()->create();

    book($this->slot, ['student_id' => $other->id])->assertForbidden();
});

it('forbids booking when the school window is closed', function () {
    $this->school->update(['close_for_contacts_at' => now()->subHour()]);

    book($this->slot)->assertForbidden();
});

it('forbids booking a slot the teacher has closed to contacts', function () {
    $this->slot->update(['contact_can_book' => false]);

    book($this->slot)->assertForbidden();
});

it('rejects booking inside the buffer', function () {
    $this->slot->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(75)]);

    book($this->slot)->assertUnprocessable()->assertJsonValidationErrors('time_slot_id');
});

it('rejects booking an already reserved slot', function () {
    $this->slot->update(['student_id' => Student::factory()->create()->id]);

    book($this->slot)->assertUnprocessable()->assertJsonValidationErrors('time_slot_id');
});

it('rejects booking with a teacher the student is not enrolled with', function () {
    $slot = seedBookableSlot(seedUser());

    book($slot)->assertUnprocessable()->assertJsonValidationErrors('student_id');
});

it('rejects booking for a student who cannot book', function () {
    $this->student->update(['can_book' => false]);

    book($this->slot)->assertUnprocessable()->assertJsonValidationErrors('student_id');
});

it('allows booking with staff who own time slots', function () {
    $counselor = seedUser();
    BouncerFacade::scope()->onceTo($this->school->id, fn () => BouncerFacade::allow($counselor)->to(Permission::ownTimeSlots->value));
    $slot = seedBookableSlot($counselor);

    book($slot)->assertOk();
    expect($slot->refresh()->student_id)->toBe($this->student->id);
});

it('rejects a second reservation with the same teacher', function () {
    seedBookableSlot($this->teacher, ['student_id' => $this->student->id, 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);

    book($this->slot)->assertUnprocessable()->assertJsonValidationErrors('student_id');
});

it('rejects an overlapping reservation for the guardian', function () {
    $otherStudent = Student::factory()->create();
    $this->guardian->students()->attach($otherStudent);
    seedBookableSlot(seedUser(), [
        'student_id' => $otherStudent->id,
        'reserved_by' => $this->guardian->id,
        'starts_at' => $this->slot->starts_at->addMinutes(5),
        'ends_at' => $this->slot->ends_at->addMinutes(5),
    ]);

    book($this->slot)->assertUnprocessable()->assertJsonValidationErrors('time_slot_id');
});

it('rejects a language the school does not offer', function () {
    book($this->slot, ['language' => 'ja'])->assertUnprocessable()->assertJsonValidationErrors('language');
});

it('rejects a language when translator requests are off', function () {
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 0, 'overlap_max' => 0]);
    $this->slot->update(['allow_translator_requests' => false]);

    book($this->slot, ['language' => 'ja'])->assertUnprocessable()->assertJsonValidationErrors('language');
});

it('enforces translator caps', function (string $cap, int $existing, int $max, bool $ok) {
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 0, 'overlap_max' => 0, $cap => $max]);

    foreach (range(1, $existing) as $i) {
        seedBookableSlot(seedUser(), [
            'student_id' => Student::factory()->create()->id,
            'language' => Language::JAPANESE,
            'starts_at' => $this->slot->starts_at,
            'ends_at' => $this->slot->ends_at,
        ]);
    }

    $response = book($this->slot, ['language' => 'ja']);

    $ok ? $response->assertOk() : $response->assertUnprocessable()->assertJsonValidationErrors('language');
})->with([
    'request_max hit' => ['request_max', 2, 2, false],
    'request_max below' => ['request_max', 1, 2, true],
    'request_max unlimited' => ['request_max', 3, 0, true],
    'overlap_max hit' => ['overlap_max', 1, 1, false],
    'overlap_max below' => ['overlap_max', 1, 2, true],
    'overlap_max unlimited' => ['overlap_max', 3, 0, true],
]);

it('ignores non-overlapping requests for overlap_max', function () {
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 0, 'overlap_max' => 1]);
    seedBookableSlot(seedUser(), [
        'student_id' => Student::factory()->create()->id,
        'language' => Language::JAPANESE,
        'starts_at' => $this->slot->ends_at,
        'ends_at' => $this->slot->ends_at->addMinutes(15),
    ]);

    book($this->slot, ['language' => 'ja'])->assertOk();
    expect($this->slot->refresh()->language)->toBe(Language::JAPANESE);
});

it('lets an admin book any student and bypass the window and buffer', function () {
    $this->actingAs($this->user);
    $this->givePermission(Permission::update, TimeSlot::class);
    $this->school->update(['close_for_contacts_at' => now()->subHour()]);
    $this->slot->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(75), 'contact_can_book' => false]);

    book($this->slot)->assertOk();

    expect($this->slot->refresh()->reserved_by)->toBe($this->user->id);
});

it('cancels a reservation', function (string $actor) {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'reserved_at' => now(), 'contact_notes' => 'x']);

    $user = match ($actor) {
        'guardian' => $this->guardian,
        'teacher' => $this->teacher,
        'admin' => tap($this->user, fn () => $this->givePermission(Permission::update, TimeSlot::class)),
    };

    $this->actingAs($user)
        ->deleteJson(route('reservations.destroy', $this->slot))
        ->assertOk();

    $this->slot->refresh();
    expect($this->slot->student_id)->toBeNull()
        ->and($this->slot->reserved_by)->toBeNull()
        ->and($this->slot->reserved_at)->toBeNull()
        ->and($this->slot->contact_notes)->toBeNull();
})->with(['guardian', 'teacher', 'admin']);

it('forbids strangers from cancelling', function () {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id]);

    $this->actingAs(seedUser(['user_type' => UserType::guardian]))
        ->deleteJson(route('reservations.destroy', $this->slot))
        ->assertForbidden();
});

it('blocks guardians from cancelling inside the buffer', function () {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(75)]);

    $this->deleteJson(route('reservations.destroy', $this->slot))->assertForbidden();
    $this->actingAs($this->teacher)->deleteJson(route('reservations.destroy', $this->slot))->assertOk();
});

it('reschedules a reservation to another slot of the same teacher', function () {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'reserved_at' => now()->subDay(), 'contact_notes' => 'x', 'translator_notes' => 'tn']);
    $target = seedBookableSlot($this->teacher, ['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);

    $this->putJson(route('reservations.update', $this->slot), ['time_slot_id' => $target->id, 'contact_notes' => 'y'])
        ->assertOk();

    $this->slot->refresh();
    $target->refresh();
    expect($this->slot->student_id)->toBeNull()
        ->and($this->slot->translator_notes)->toBeNull()
        ->and($target->student_id)->toBe($this->student->id)
        ->and($target->reserved_by)->toBe($this->guardian->id)
        ->and($target->reserved_at->isSameDay(now()->subDay()))->toBeTrue()
        ->and($target->contact_notes)->toBe('y')
        ->and($target->translator_notes)->toBe('tn');
});

it('cannot reschedule to a different teacher', function () {
    $this->slot->update(['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id]);
    $target = seedBookableSlot(seedUser());

    $this->putJson(route('reservations.update', $this->slot), ['time_slot_id' => $target->id])
        ->assertUnprocessable();
    expect($this->slot->refresh()->student_id)->toBe($this->student->id);
});

it('rejects a second booking of the same slot', function () {
    book($this->slot)->assertOk();

    $other = Student::factory()->create();
    $otherGuardian = seedGuardian($other);
    $this->section->students()->attach($other);

    $this->actingAs($otherGuardian);
    book($this->slot, ['student_id' => $other->id])->assertUnprocessable();
    expect($this->slot->refresh()->student_id)->toBe($this->student->id);
});

it('shows the booking page to contacts and admins only', function () {
    $stranger = seedGuardian(Student::factory()->create());

    $this->actingAs($stranger)->get(route('reservations.create', [$this->student, $this->teacher]))->assertForbidden();
    $this->actingAs($this->guardian)->get(route('reservations.create', [$this->student, seedUser()]))->assertForbidden();

    $this->actingAs($this->guardian)
        ->get(route('reservations.create', [$this->student, $this->teacher]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reservations/Create')
            ->where('student.id', $this->student->id)
            ->where('staff.id', $this->teacher->id)
            ->has('slots', 1)
            ->where('existingReservation', null));

    $this->actingAs($this->user);
    $this->givePermission(Permission::update, TimeSlot::class)
        ->get(route('reservations.create', [$this->student, $this->teacher]))
        ->assertOk();
});

it('lists only bookable slots on the booking page', function () {
    seedBookableSlot($this->teacher, ['contact_can_book' => false]);
    seedBookableSlot($this->teacher, ['student_id' => Student::factory()->create()->id]);
    seedBookableSlot($this->teacher, ['starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(75)]);
    seedBookableSlot($this->teacher, ['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addMinutes(15)]);
    $existing = seedBookableSlot($this->teacher, ['student_id' => $this->student->id, 'reserved_by' => $this->guardian->id, 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addMinutes(15)]);

    $this->get(route('reservations.create', [$this->student, $this->teacher]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('slots', 1)
            ->where('slots.0.id', $this->slot->id)
            ->where('existingReservation.id', $existing->id));
});

it('redirects inertia bookings to the dashboard', function () {
    $this->post(route('reservations.store', $this->slot), ['student_id' => $this->student->id], ['X-Inertia' => 'true'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success');
});
