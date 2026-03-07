<?php

use App\Enums\Permission;
use App\Models\Batch;
use App\Models\Student;
use App\Models\TimeSlot;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn()->setSchool();

    $this->school->update([
        'timezone' => 'Asia/Shanghai',
        'open_for_teachers_at' => now()->subDay(),
    ]);
});

it('has batch page', function () {
    $this->givePermission(Permission::viewAny, TimeSlot::class)
        ->get(route('batches.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/batches/Index')
            ->has('title')
            ->has('batches')
            ->has('breadcrumbs')
        );
});

it('redirects from create with error when no selection', function () {
    $this->givePermission(Permission::create, TimeSlot::class)
        ->get(route('batches.create'))
        ->assertRedirect(route('teachers.index'))
        ->assertSessionHas('error');
});

it("can't store a batch without permission", function () {
    $this->post(route('batches.store'))
        ->assertForbidden();
});

it('can store a batch from selection', function () {
    $user = seedUser();
    $this->user->toggleSelectedModelInstance($user);

    $this->givePermission(Permission::create, TimeSlot::class)
        ->post(route('batches.store'))
        ->assertRedirect();

    expect(Batch::where('user_id', $this->user->id)->exists())->toBeTrue();
});

it("can't view batch edit page without permission", function () {
    $batch = seedBatch();

    $this->get(route('batches.edit', $batch))
        ->assertForbidden();
});

it('can view the batch edit page', function () {
    $batch = seedBatch();

    $this->givePermission(Permission::update, TimeSlot::class)
        ->get(route('batches.edit', $batch))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('time-slots/batches/Edit')
            ->has('title')
            ->has('batch')
            ->has('breadcrumbs')
        );
});

it('can update batch time slots', function () {
    $batch = seedBatch();
    $data = makeTimeSlotRequest(['batch_id' => $batch->id]);

    $this->givePermission(Permission::update, TimeSlot::class)
        ->putJson(route('batches.update', $batch), $data)
        ->assertOk()
        ->assertJsonStructure(['level', 'message', 'data']);
});

it('can delete batch time slots by time window', function () {
    $batch = seedBatch();
    $timeSlot = $batch->timeSlots()->first();

    fullPermissions()
        ->postJson("/batches/{$batch->id}/delete", [
            'starts_at' => $timeSlot->starts_at->toDateTimeString(),
            'ends_at' => $timeSlot->ends_at->toDateTimeString(),
        ])
        ->assertOk();

    expect($batch->timeSlots()->where('starts_at', $timeSlot->starts_at)->exists())
        ->toBeFalse();
});

it('does not delete booked batch time slots', function () {
    $batch = seedBatch();
    $timeSlot = $batch->timeSlots()->first();
    $timeSlot->update(['student_id' => Student::factory()->create()->id]);

    fullPermissions()
        ->postJson("/batches/{$batch->id}/delete", [
            'starts_at' => $timeSlot->starts_at->toDateTimeString(),
            'ends_at' => $timeSlot->ends_at->toDateTimeString(),
        ])
        ->assertOk();

    expect($batch->timeSlots()->where('starts_at', $timeSlot->starts_at)->exists())
        ->toBeTrue();
});

it('returns batch event source', function () {
    $batch = seedBatch();

    $response = $this->getJson(route('batches.event-source', $batch).'?start=2024-01-01&end=2024-12-31')
        ->assertOk();

    $content = $response->getContent();
    $data = json_decode($content, true);

    expect($data)->toBeArray();
});
