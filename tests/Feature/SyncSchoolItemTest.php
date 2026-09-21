<?php

use App\Enums\Permission;
use App\Jobs\SyncSchoolItem;
use App\Jobs\SyncSection;
use App\Models\User;
use App\Notifications\SyncCompleted;
use App\SisProviders\PowerSchoolProvider;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
    setSchool();
    givePermission(Permission::editSchoolSettings);
});

it('queues a school item sync', function () {
    Bus::fake();

    $this->post(route('settings.school.item-sync', 'courses'))
        ->assertRedirect()
        ->assertSessionHas('success');

    Bus::assertDispatched(SyncSchoolItem::class, fn (SyncSchoolItem $job) => $job->school->is($this->school)
        && $job->item === 'courses'
        && $job->user->is($this->user));
});

it('queues a whole-school sync from the model sync endpoint', function () {
    Bus::fake();

    $this->post(route('model.sync', ['school', $this->school->id]))
        ->assertRedirect();

    Bus::assertDispatched(SyncSchoolItem::class, fn (SyncSchoolItem $job) => $job->item === 'school');
});

it('flags the item as syncing until the job finishes', function () {
    Bus::fake();
    Notification::fake();

    $this->post(route('settings.school.item-sync', 'courses'));

    $this->get(route('settings.school.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('syncing', ['courses']));

    $this->mock(PowerSchoolProvider::class)->shouldReceive('syncSchoolCourses')->once();
    (new SyncSchoolItem($this->school, 'courses', $this->user))->handle();

    expect(Cache::has(SyncSchoolItem::syncingKey($this->school, 'courses')))->toBeFalse();
});

it('flags every item for a whole-school sync and clears each as it finishes', function () {
    Bus::fake();
    Notification::fake();
    $key = fn (string $item) => SyncSchoolItem::syncingKey($this->school, $item);

    $this->post(route('model.sync', ['school', $this->school->id]));

    expect(array_map(fn ($item) => Cache::has($key($item)), ['school', 'staff', 'students', 'courses', 'sections']))
        ->toBe([true, true, true, true, true]);

    $mock = $this->mock(PowerSchoolProvider::class);
    $mock->shouldReceive('syncSchool')->once()->andReturn($this->school);
    $mock->shouldReceive('syncSchoolStaff')->once()->andReturnUsing(function () use ($mock, $key) {
        expect(Cache::has($key('school')))->toBeFalse()->and(Cache::has($key('staff')))->toBeTrue();

        return $mock;
    });
    $mock->shouldReceive('syncSchoolStudents', 'syncSchoolCourses', 'syncSchoolSections')->andReturn($mock);

    (new SyncSchoolItem($this->school, 'school', $this->user))->handle();

    expect(array_map(fn ($item) => Cache::has($key($item)), ['school', 'staff', 'students', 'courses', 'sections']))
        ->toBe([false, false, false, false, false]);
});

it('clears the syncing flag when the job fails', function () {
    Notification::fake();
    Cache::put(SyncSchoolItem::syncingKey($this->school, 'staff'), true, 60);

    (new SyncSchoolItem($this->school, 'staff', $this->user))->failed(new Exception('boom'));

    expect(Cache::has(SyncSchoolItem::syncingKey($this->school, 'staff')))->toBeFalse();
});

it('rejects unknown sync items', function () {
    Bus::fake();

    $this->post(route('settings.school.item-sync', 'school'))->assertNotFound();
    $this->post(route('settings.school.item-sync', 'nope'))->assertNotFound();

    Bus::assertNothingDispatched();
});

it('notifies the user when the sync finishes', function () {
    Notification::fake();
    $this->mock(PowerSchoolProvider::class)
        ->shouldReceive('syncSchoolCourses')
        ->once();

    (new SyncSchoolItem($this->school, 'courses', $this->user))->handle();

    Notification::assertSentTo($this->user, SyncCompleted::class, fn (SyncCompleted $n) => $n->item === 'courses'
        && $n->level === 'success'
        && $n->via($this->user) === ['broadcast']);
});

it('notifies the user when the sync fails', function () {
    Notification::fake();

    (new SyncSchoolItem($this->school, 'staff', $this->user))->failed(new Exception('boom'));

    Notification::assertSentTo($this->user, SyncCompleted::class, fn (SyncCompleted $n) => $n->level === 'error');
});

it('authorizes only the user on their notification channel', function () {
    $auth = fn (int $id) => $this->post('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => "private-App.Models.User.{$id}",
    ]);

    $auth($this->user->id)->assertOk();

    $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auth($other->id)->assertForbidden();
});

it('syncs the whole school in dependency order', function () {
    Notification::fake();
    Bus::fake([SyncSection::class]);
    $sections = collect([seedSection(), seedSection()]);
    $order = [];
    $mock = $this->mock(PowerSchoolProvider::class);

    foreach (['syncSchool', 'syncSchoolStaff', 'syncSchoolStudents', 'syncSchoolCourses', 'syncSchoolSections'] as $method) {
        $mock->shouldReceive($method)->once()->andReturnUsing(function () use (&$order, $method, $mock) {
            $order[] = $method;

            return $method === 'syncSchool' ? $this->school : $mock;
        });
    }

    (new SyncSchoolItem($this->school, 'school', $this->user))->handle();

    expect($order)->toBe(['syncSchool', 'syncSchoolStaff', 'syncSchoolStudents', 'syncSchoolCourses', 'syncSchoolSections']);
    Notification::assertSentTo($this->user, SyncCompleted::class, fn (SyncCompleted $n) => $n->item === 'school' && $n->level === 'success');
    Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 2
        && $batch->jobs->first()->section->is($sections->first())
        && $batch->allowsFailures());
});

it('exposes enrollment sync progress on the settings page', function () {
    Bus::fake();
    seedSection();
    seedSection();

    SyncSchoolItem::dispatchEnrollmentBatch($this->school, $this->user);

    $this->get(route('settings.school.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('enrollmentSync.total', 2)
            ->where('enrollmentSync.processed', 0));
});

it('does not fan out section jobs for a non-section item', function () {
    Notification::fake();
    Bus::fake([SyncSection::class]);
    seedSection();
    $this->mock(PowerSchoolProvider::class)->shouldReceive('syncSchoolStaff')->once();

    (new SyncSchoolItem($this->school, 'staff', $this->user))->handle();

    Bus::assertNothingBatched();
});
