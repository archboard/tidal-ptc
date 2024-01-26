<?php

beforeEach(function () {
    logIn()->setSchool();
});

it("can't update flag without permission", function () {
    $course = \App\Models\Course::factory()->create();

    $this->put(route('toggle-hidden'), [
        'model' => 'course',
        'id' => $course->id,
    ])->assertForbidden();
});

it('can update flag not using json', function () {
    $course = \App\Models\Course::factory()->create(['can_book' => false]);

    $this->assertFalse($course->can_book);

    $this->givePermission(\App\Enums\Permission::update, \App\Models\Course::class)
        ->put(route('toggle-hidden'), [
            'model' => 'course',
            'id' => $course->id,
        ])
        ->assertSessionHas('success')
        ->assertRedirect();

    $course->refresh();
    $this->assertTrue($course->can_book);
});

it('can update flag using json', function () {
    $course = \App\Models\Course::factory()->create(['can_book' => true]);

    $this->assertTrue($course->can_book);

    $this->givePermission(\App\Enums\Permission::update, \App\Models\Course::class)
        ->putJson(route('toggle-hidden'), [
            'model' => 'course',
            'id' => $course->id,
        ])
        ->assertJsonStructure(['level', 'message']);

    $course->refresh();
    $this->assertFalse($course->can_book);
});
