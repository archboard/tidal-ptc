<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it("can't be accessed without permission", function () {
    $this->get(route('settings.school.edit'))
        ->assertForbidden();
});

it('can be accessed with permission', function () {
    fullPermissions()->setSchool();

    $this->get(route('settings.school.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('school')
        )
        ->assertOk();
});

it('can update time slot settings', function (array $data) {
    fullPermissions()->setSchool();

    $this->put(route('settings.school.update'), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->school->refresh();
    $this->assertEquals($data['timezone'], $this->school->timezone);
    $this->assertEquals($data['allow_online_meetings'], $this->school->allow_online_meetings);
    $this->assertEquals($data['allow_translator_requests'], $this->school->allow_translator_requests);
    $this->assertEquals($data['booking_buffer_hours'], $this->school->booking_buffer_hours);

    // Check that the timezones are correctly converted to UTC
    $dates = [
        'open_for_contacts_at',
        'close_for_contacts_at',
        'open_for_teachers_at',
        'close_for_teachers_at',
    ];

    foreach ($dates as $key) {
        $this->assertTrue($this->school->$key->isSameAs($this->school->dateToApp($data[$key])));
    }
})->with([
    'all options' => fn () => [
        'timezone' => fake()->timezone(),
        'allow_online_meetings' => fake()->boolean(),
        'allow_translator_requests' => fake()->boolean(),
        'booking_buffer_hours' => fake()->numberBetween(0, 72),
        'open_for_contacts_at' => today()->format('Y-m-d H:i'),
        'close_for_contacts_at' => today()->addWeek()->format('Y-m-d H:i'),
        'open_for_teachers_at' => today()->format('Y-m-d H:i'),
        'close_for_teachers_at' => today()->addWeek()->format('Y-m-d H:i'),
    ],
]);
