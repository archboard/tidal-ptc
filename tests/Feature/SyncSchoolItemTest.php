<?php

use App\Enums\Permission;
use App\Jobs\SyncSchoolItem;
use App\Models\User;
use App\Notifications\SyncCompleted;
use App\SisProviders\PowerSchoolProvider;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

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
