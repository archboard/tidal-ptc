<?php

use App\Enums\Language;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
    setSchool();
});

it("can't be accessed without permission", function () {
    $this->get(route('settings.school.edit'))
        ->assertForbidden();
});

it('can be accessed with permission', function () {
    fullPermissions();

    $this->get(route('settings.school.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('title')
            ->has('school')
        )
        ->assertOk();
});

it('can update time slot settings', function (array $data) {
    fullPermissions();

    $this->put(route('settings.school.update'), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->school->refresh();
    $this->assertEquals($data['timezone'], $this->school->timezone);
    $this->assertEquals($data['allow_online_meetings'], $this->school->allow_online_meetings);
    $this->assertEquals($data['allow_translator_requests'], $this->school->allow_translator_requests);
    $this->assertEquals($data['booking_buffer_hours'], $this->school->booking_buffer_hours);

    $dates = [
        'open_for_contacts_at',
        'close_for_contacts_at',
        'open_for_teachers_at',
        'close_for_teachers_at',
    ];

    foreach ($dates as $key) {
        if ($data[$key]) {
            $this->assertTrue($this->school->$key->equalTo($this->school->dateToApp($data[$key])));
        } else {
            $this->assertNull($this->school->$key);
        }
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
    'no close date' => fn () => [
        'timezone' => fake()->timezone(),
        'allow_online_meetings' => fake()->boolean(),
        'allow_translator_requests' => fake()->boolean(),
        'booking_buffer_hours' => fake()->numberBetween(0, 72),
        'open_for_contacts_at' => today()->format('Y-m-d H:i'),
        'close_for_contacts_at' => null,
        'open_for_teachers_at' => today()->format('Y-m-d H:i'),
        'close_for_teachers_at' => null,
    ],
    'no open date' => fn () => [
        'timezone' => fake()->timezone(),
        'allow_online_meetings' => fake()->boolean(),
        'allow_translator_requests' => fake()->boolean(),
        'booking_buffer_hours' => fake()->numberBetween(0, 72),
        'open_for_contacts_at' => null,
        'close_for_contacts_at' => today()->addWeek()->format('Y-m-d H:i'),
        'open_for_teachers_at' => null,
        'close_for_teachers_at' => today()->addWeek()->format('Y-m-d H:i'),
    ],
]);

it('can save languages', function (array $data) {
    fullPermissions();

    $this->put(route('settings.school.languages'), ['languages' => $data])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->school->refresh();
    $this->school->load('languages');

    foreach ($data as $lang) {
        $this->assertNotNull(
            $this->school->languages->firstWhere(fn ($language) => $language->language_code->value === $lang['code']
                && $language->request_max === $lang['request_max']
                && $language->overlap_max === $lang['overlap_max']
            )
        );
    }
})->with([
    'no existing languages' => function () {
        $languages = Language::cases();

        return collect($languages)->random(fake()->numberBetween(1, count($languages)))
            ->map(fn (Language $language) => [
                'code' => $language->value,
                'request_max' => fake()->numberBetween(1, 10),
                'overlap_max' => fake()->numberBetween(1, 10),
            ])
            ->toArray();
    },
    'with existing language' => function () {
        $languages = Language::cases();
        $existing = collect($languages)->random();

        $this->school->languages()->create([
            'language_code' => $existing->value,
            'request_max' => fake()->numberBetween(1, 10),
            'overlap_max' => fake()->numberBetween(1, 10),
        ]);

        return collect($languages)->filter(fn (Language $language) => $language->value !== $existing->value)
            ->map(fn (Language $language) => [
                'code' => $language->value,
                'request_max' => fake()->numberBetween(1, 10),
                'overlap_max' => fake()->numberBetween(1, 10),
            ])
            ->toArray();
    },
]);
