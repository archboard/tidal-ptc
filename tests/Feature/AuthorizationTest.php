<?php

use App\Enums\Permission;
use App\Jobs\SyncSchoolItem;
use App\Models\Course;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Silber\Bouncer\BouncerFacade;

beforeEach(function () {
    logIn();
    setSchool();
    $this->student = Student::factory()->create();
    $this->course = Course::factory()->create();
});

function grant(Permission $permission, ?string $model = null): void
{
    test()->givePermission($permission, $model);
    BouncerFacade::refresh();
}

it('forbids record pages without permission and allows them with it', function (string $route, Permission $permission, string $model) {
    $url = fn () => route($route, match (true) {
        str_ends_with($route, '.show') => [match ($model) {
            Student::class => $this->student,
            Course::class => $this->course,
            default => seedUser(),
        }],
        default => [],
    });

    $this->get($url())->assertForbidden();

    grant($permission, $model);
    $this->get($url())->assertOk();
})->with([
    'students index' => ['students.index', Permission::viewAny, Student::class],
    'students show' => ['students.show', Permission::view, Student::class],
    'courses index' => ['courses.index', Permission::viewAny, Course::class],
    'courses show' => ['courses.show', Permission::view, Course::class],
    'users index' => ['users.index', Permission::viewAny, User::class],
    'users show' => ['users.show', Permission::view, User::class],
]);

it('forbids the batch event source without view list permission on time slots', function () {
    $batch = seedBatch();
    $url = route('batches.event-source', $batch).'?start=2024-01-01&end=2024-12-31';

    grant(Permission::viewAny, TimeSlot::class);
    $this->getJson($url)->assertOk();
});

it('only searches whitelisted models the user may list', function () {
    $this->postJson(route('model.search', 'tenant'))->assertNotFound();
    $this->postJson(route('model.search', 'user'))->assertForbidden();

    grant(Permission::viewAny, User::class);
    $this->postJson(route('model.search', 'user'))->assertOk();
});

it('lets users sync themselves but not others without permission', function () {
    $this->post(route('model.sync', ['user', $this->user->id]))->assertRedirect();
    $this->post(route('model.sync', ['user', seedUser()->id]))->assertForbidden();
    $this->post(route('model.sync', ['user', 999999]))->assertNotFound();
    $this->post(route('model.sync', ['nope', 1]))->assertNotFound();
});

it('requires edit school settings to sync a school', function () {
    Bus::fake();

    $this->post(route('model.sync', ['school', $this->school->id]))->assertForbidden();

    grant(Permission::editSchoolSettings);
    $this->post(route('model.sync', ['school', $this->school->id]))->assertRedirect();
    Bus::assertDispatched(SyncSchoolItem::class);
});
