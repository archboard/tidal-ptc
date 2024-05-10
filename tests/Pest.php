<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function logIn(array $attributes = [])
{
    return test()->logIn(attributes: $attributes);
}

function fullPermissions()
{
    return test()->fullPermission();
}

function seedUser(array $attributes = []): App\Models\User
{
    return test()->seedUser($attributes);
}

function givePermission(App\Enums\Permission $permission)
{
    return test()->givePermission($permission);
}

function setSchool()
{
    return test()->setSchool();
}

function makeTimeSlotRequest(array $attributes = []): array
{
    $start = now()->addDay();

    return [
        'starts_at' => $start->toIso8601ZuluString(),
        'ends_at' => $start->addMinutes(15)->toIso8601ZuluString(),
        'batch_id' => null,
        'teacher_notes' => fake()->sentence(),
        'location' => fake()->word(),
        'meeting_url' => fake()->url(),
        'is_online' => fake()->boolean(),
        'contact_can_book' => fake()->boolean(),
        'allow_translator_requests' => fake()->boolean(),
        'allow_online_meetings' => fake()->boolean(),
        ...$attributes,
    ];
}

function seedTimeSlot(array $attributes = []): App\Models\TimeSlot
{
    return test()->seedTimeSlot($attributes);
}
