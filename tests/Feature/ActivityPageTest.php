<?php

use App\Enums\ActivityEvent;
use App\Enums\Permission;
use App\Models\Activity;
use App\Models\TimeSlot;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();
    $this->school->update(['open_for_teachers_at' => now()->subDay()]);
});

it('requires permission to view activity', function () {
    $this->get(route('activity.index'))->assertForbidden();
});

it('lists activity for the current school with filters', function () {
    $slot = seedTimeSlot();
    activity()->performedOn($slot)->event('reservation_booked')->log('reservation_booked');
    activity()->performedOn($this->school)->event('updated')->log('updated');
    Activity::create(['description' => 'other', 'event' => 'reservation_booked', 'school_id' => $this->school->id + 1]);
    $inSchool = Activity::where('school_id', $this->school->id)->count();

    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('activity.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('activity/Index')
            ->has('activities.data', $inSchool)
            ->has('events')
            ->has('subjectTypes'));

    $this->get(route('activity.index', ['event' => 'reservation_booked']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.subject_id', $slot->id)
            ->where('activities.data.0.causer', $this->user->name));

    $this->get(route('activity.index', ['from' => now()->addDay()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('activities.data', 0));
});

it('returns slot history to those who may view the reservation', function () {
    $owner = seedUser();
    $slot = TimeSlot::factory()->for($owner)->create();
    $slot->update(['location' => 'Moved']);

    $this->getJson(route('time-slots.activity', $slot))->assertForbidden();

    $this->actingAs($owner)
        ->getJson(route('time-slots.activity', $slot))
        ->assertOk()
        ->assertJsonPath('0.event', 'updated')
        ->assertJsonPath('0.changes.attributes.location', 'Moved');
});

it('stores english descriptions and translates them for display', function () {
    $slot = seedTimeSlot();
    ActivityEvent::batch_created->log($slot, ['count' => 3]);
    ActivityEvent::notification_sent->log($this->user, ['event' => 'slot_booked', 'channel' => 'mail']);

    expect(Activity::latest('id')->first()->description)->toBe(':notification email sent');

    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('activity.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activities.data.0.description', 'Reservation booked email sent')
            ->where('activities.data.1.description', 'Time slots created for 3 teachers'));
});
