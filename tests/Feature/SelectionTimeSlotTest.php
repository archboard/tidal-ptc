<?php

use App\Enums\Permission;
use App\Models\TimeSlot;

beforeEach(function () {
    logIn()->setSchool();

    $this->user->toggleSelectedModelInstance(seedUser());
    $this->user->toggleSelectedModelInstance(seedUser());
});

it("can't create without permission", function () {
    $this->postJson(route('time-slots.store'), makeTimeSlotRequest())
        ->assertForbidden();
});

it('can create slots for selection', function () {
    $data = makeTimeSlotRequest();
    $this->givePermission(Permission::create, TimeSlot::class)
        ->postJson(route('time-slots.store'), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);

    $timeSlots = TimeSlot::all();

    $this->assertEquals(2, $timeSlots->count());
    $this->assertTrue(
        $timeSlots->every(function (TimeSlot $timeSlot) use ($data) {
            return $timeSlot->created_by === $this->user->id;
        })
    );
});
