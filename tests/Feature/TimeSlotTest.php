<?php

use App\Enums\Permission;
use App\Http\Requests\UpdateTimeSlotRequest;
use App\Models\Student;
use App\Models\TimeSlot;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Silber\Bouncer\BouncerFacade;

beforeEach(function () {
    logIn()->setSchool();

    $this->school->update([
        'timezone' => 'Asia/Shanghai',
        'open_for_teachers_at' => now()->subDay(),
    ]);
});

it('has time slots page', function () {
    $this->get(route('time-slots.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/Index')
            ->has('title')
            ->has('eventSources')
            ->has('breadcrumbs')
        );
});

it('can check creation authorization', function () {
    $this->get(route('time-slots.create'))
        ->assertForbidden();
});

it('can view the create time slot page', function () {
    $this->givePermission(Permission::create, TimeSlot::class)
        ->get(route('time-slots.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/Manage')
            ->has('title')
            ->has('events')
            ->has('breadcrumbs')
        );
});

it("can't create time slots for others without permission", function () {
    $data = makeTimeSlotRequest(['user_id' => seedUser()->id]);

    $this->post(route('time-slots.store'), $data)
        ->assertForbidden();
});

it('can create time slots with permission', function () {
    $user = seedUser();
    $data = makeTimeSlotRequest(['user_id' => $user->id]);

    $this->givePermission(Permission::create, TimeSlot::class)
        ->post(route('time-slots.store'), $data)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('level', 'success')
            ->has('message')
            ->has('data')
        );

    expect($this->user->timeSlots()->count())->toBe(0)
        ->and($user->timeSlots()->first())->toMatchRequestData($data);
});

it('can create a time slot for self', function () {
    seedSection();
    $data = makeTimeSlotRequest(['user_id' => $this->user->id]);

    $this->post(route('time-slots.store'), $data)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('level', 'success')
            ->has('message')
            ->has('data')
        );

    expect($this->user->timeSlots()->count())->toBe(1)
        ->and($this->user->timeSlots()->first())->toMatchRequestData($data);
});

it("can't update a slot for another user without permission", function () {
    $user = seedUser();
    $data = makeTimeSlotRequest(['user_id' => $user->id]);
    $timeSlot = TimeSlot::factory()->for($user)->create();

    $this->putJson(route('time-slots.update', $timeSlot), $data)
        ->assertForbidden();
});

it('can update a slot for another user with permission', function () {
    $user = seedUser();
    $data = makeTimeSlotRequest(['user_id' => $user->id]);
    $timeSlot = TimeSlot::factory()->for($user)->create();

    $this->givePermission(Permission::update, $timeSlot)
        ->putJson(route('time-slots.update', $timeSlot), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);

    $timeSlot->refresh();
    expect($timeSlot)->toMatchRequestData($data, UpdateTimeSlotRequest::class);
});

it('can update own slot', function () {
    $data = makeTimeSlotRequest(['user_id' => $this->user->id]);
    $timeSlot = TimeSlot::factory()->for($this->user)->create();

    $this->putJson(route('time-slots.update', $timeSlot), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);

    $timeSlot->refresh();
    expect($timeSlot)->toMatchRequestData($data, UpdateTimeSlotRequest::class);
});

it('can update a time slot batch', function () {
    $batch = seedBatch();
    $data = makeTimeSlotRequest(['batch_id' => $batch->id]);

    $timeSlot = $batch->timeSlots()->first();
    $this->givePermission(Permission::update, TimeSlot::class)
        ->put(route('time-slots.update', $timeSlot), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);

    $timeSlot->refresh();
    expect($timeSlot)->toMatchRequestData($data, UpdateTimeSlotRequest::class);
});

it("can't delete another user's slot without permission", function () {
    $user = seedUser();
    $timeSlot = TimeSlot::factory()->for($user)->create();

    $this->deleteJson(route('time-slots.destroy', $timeSlot))
        ->assertForbidden();
});

it('can delete another user slot with permission', function () {
    $user = seedUser();
    $timeSlot = TimeSlot::factory()->for($user)->create();

    $this->givePermission(Permission::delete, $timeSlot)
        ->deleteJson(route('time-slots.destroy', $timeSlot))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('level', 'success')
            ->has('message')
        );

    expect(TimeSlot::find($timeSlot->id))->toBeNull();
});

it('can delete own slot', function () {
    $timeSlot = TimeSlot::factory()->for($this->user)->create();

    $this->deleteJson(route('time-slots.destroy', $timeSlot))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('level', 'success')
            ->has('message')
        );

    expect(TimeSlot::find($timeSlot->id))->toBeNull();
});

it('determines whether a student can meet with staff', function () {
    $teacher = seedUser();
    $section = seedSection($teacher);
    /** @var Student $student */
    $student = $section->students->first();
    $stranger = seedUser();

    expect($student->canMeetWith($teacher))->toBeTrue()
        ->and($student->canMeetWith($stranger))->toBeFalse();

    $section->update(['alt_user_id' => $stranger->id]);
    expect($student->canMeetWith($stranger))->toBeTrue();

    $section->update(['can_book' => false]);
    expect($student->canMeetWith($teacher))->toBeFalse();

    $section->update(['can_book' => true]);
    $section->course->update(['can_book' => false]);
    expect($student->canMeetWith($teacher))->toBeFalse();

    $this->givePermission(Permission::ownTimeSlots);
    expect(BouncerFacade::scope()->onceTo($this->school->id, fn () => $student->canMeetWith($this->user)))->toBeTrue();
});

it('scopes bookable time slots', function () {
    $this->school->update(['booking_buffer_hours' => 2]);
    $bookable = seedTimeSlot();
    seedTimeSlot(['contact_can_book' => false]);
    seedTimeSlot(['student_id' => Student::factory()->create()->id]);
    seedTimeSlot(['starts_at' => now()->addHour(), 'ends_at' => now()->addMinutes(75)]);
    seedTimeSlot(['school_id' => $this->tenant->schools->firstWhere('id', '!=', $this->school->id)->id]);

    expect(TimeSlot::bookable($this->school)->pluck('id')->all())->toBe([$bookable->id])
        ->and(TimeSlot::reserved()->count())->toBe(1);
});

it('excludes slots starting exactly at the booking buffer', function () {
    $this->school->update(['booking_buffer_hours' => 2]);
    $this->travelTo(now()->startOfMinute());
    seedTimeSlot(['starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(2)->addMinutes(15)]);
    $justAfter = seedTimeSlot(['starts_at' => now()->addHours(2)->addSecond(), 'ends_at' => now()->addHours(2)->addMinutes(15)]);

    expect(TimeSlot::bookable($this->school)->pluck('id')->all())->toBe([$justAfter->id]);
});

it('titles reserved slots with the student name in the calendar', function () {
    $student = Student::factory()->create();
    $timeSlot = seedTimeSlot(['student_id' => $student->id]);

    expect($timeSlot->toFullCalendar())
        ->title->toBe($student->name)
        ->classNames->toBe(['reserved']);
});

it("can't move a reserved slot without update permission", function () {
    $timeSlot = seedTimeSlot(['student_id' => Student::factory()->create()->id]);
    $moved = makeTimeSlotRequest(['starts_at' => now()->addDays(2)->toIso8601ZuluString(), 'ends_at' => now()->addDays(2)->addMinutes(15)->toIso8601ZuluString()]);
    $unmoved = [...$moved, 'starts_at' => $timeSlot->starts_at->toIso8601ZuluString(), 'ends_at' => $timeSlot->ends_at->toIso8601ZuluString()];

    $this->putJson(route('time-slots.update', $timeSlot), $moved)
        ->assertForbidden();
    $this->putJson(route('time-slots.update', $timeSlot), $unmoved)
        ->assertOk();
});

it('can move a reserved slot with update permission', function () {
    $timeSlot = seedTimeSlot(['student_id' => Student::factory()->create()->id]);
    $moved = makeTimeSlotRequest(['starts_at' => now()->addDays(2)->toIso8601ZuluString(), 'ends_at' => now()->addDays(2)->addMinutes(15)->toIso8601ZuluString()]);

    $this->givePermission(Permission::update, TimeSlot::class)
        ->putJson(route('time-slots.update', $timeSlot), $moved)
        ->assertOk();
});

it('hides reservation details from users who cannot view them', function () {
    $owner = seedUser();
    $student = Student::factory()->create();
    TimeSlot::factory()->for($owner)->create(['student_id' => $student->id, 'contact_notes' => 'secret']);
    $range = ['start' => now()->toDateString(), 'end' => now()->addWeek()->toDateString()];

    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->getJson(route('users.event-source', [$owner, ...$range]))
        ->assertOk()
        ->assertJsonMissingPath('0.extendedProps.contact_notes')
        ->assertJsonMissingPath('0.extendedProps.student');

    $this->actingAs($owner)
        ->getJson(route('users.event-source', [$owner, ...$range]))
        ->assertOk()
        ->assertJsonPath('0.extendedProps.contact_notes', 'secret')
        ->assertJsonPath('0.extendedProps.student.name', $student->name);
});

it('authorizes user and student event sources', function () {
    $other = seedUser();
    $student = Student::factory()->create();
    $range = ['start' => now()->toDateString(), 'end' => now()->addWeek()->toDateString()];

    $this->getJson(route('users.event-source', [$other, ...$range]))->assertForbidden();
    $this->getJson(route('users.event-source', [$this->user, ...$range]))->assertOk();
    $this->getJson(route('students.event-source', [$student, ...$range]))->assertForbidden();

    $this->user->students()->attach($student);
    $this->getJson(route('students.event-source', [$student, ...$range]))->assertOk();
});

it('allows event sources for any user with view permission', function () {
    $range = ['start' => now()->toDateString(), 'end' => now()->addWeek()->toDateString()];

    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->getJson(route('users.event-source', [seedUser(), ...$range]))
        ->assertOk();
    $this->getJson(route('students.event-source', [Student::factory()->create(), ...$range]))
        ->assertOk();
});
