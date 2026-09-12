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
    $this->school->update(['booking_buffer_hours' => 2]);
});

it('shows a guardian their students, teachers, other staff and reservations', function () {
    $teacher = seedUser(['user_type' => UserType::staff]);
    $student = seedSection($teacher)->students->first();
    $guardian = seedGuardian($student);
    $counselor = seedUser(['user_type' => UserType::staff]);
    BouncerFacade::scope()->onceTo($this->school->id, fn () => BouncerFacade::allow($counselor)->to(Permission::ownTimeSlots->value));
    seedBookableSlot($counselor);
    seedBookableSlot($teacher, ['student_id' => $student->id, 'reserved_by' => $guardian->id]);

    $this->actingAs($guardian)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('students', 1, fn (AssertableInertia $s) => $s->where('id', $student->id)->has('sections.0.teacher')->etc())
            ->has('otherStaff', 1, fn (AssertableInertia $s) => $s->where('id', $counselor->id)->etc())
            ->has('reservations', 1, fn (AssertableInertia $r) => $r->where('user.id', $teacher->id)->etc())
            ->where('bookingOpen', true)
            ->missing('schoolStats'));
});

it('shows staff their reservations and admins school stats', function () {
    $this->user->update(['user_type' => UserType::staff]);
    $student = Student::factory()->create();
    seedTimeSlot(['student_id' => $student->id]);
    seedTimeSlot(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('myReservations', 1)
            ->where('openCount', 1)
            ->missing('schoolStats'));
});

it('shows admins school stats', function () {
    $this->user->update(['user_type' => UserType::staff]);
    seedTimeSlot(['student_id' => Student::factory()->create()->id, 'language' => Language::JAPANESE]);
    seedTimeSlot(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15)]);
    $this->school->languages()->create(['language' => Language::JAPANESE, 'request_max' => 5, 'overlap_max' => 0]);

    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('schoolStats.slots', 2)
            ->where('schoolStats.reserved', 1)
            ->where('schoolStats.translators.0.used', 1)
            ->where('schoolStats.translators.0.max', 5));
});

it('renders an empty dashboard for students', function () {
    $this->user->update(['user_type' => UserType::student]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard')->missing('students')->missing('myReservations'));
});
