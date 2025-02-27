<?php

use App\Enums\Permission;
use App\Models\TimeSlot;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;

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
    expect($timeSlot)->toMatchRequestData($data, \App\Http\Requests\UpdateTimeSlotRequest::class);
});

it('can update own slot', function () {
    $data = makeTimeSlotRequest(['user_id' => $this->user->id]);
    $timeSlot = TimeSlot::factory()->for($this->user)->create();

    $this->putJson(route('time-slots.update', $timeSlot), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);

    $timeSlot->refresh();
    expect($timeSlot)->toMatchRequestData($data, \App\Http\Requests\UpdateTimeSlotRequest::class);
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
    expect($timeSlot)->toMatchRequestData($data, \App\Http\Requests\UpdateTimeSlotRequest::class);
});
