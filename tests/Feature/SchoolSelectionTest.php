<?php

use App\Enums\UserType;
use App\Exceptions\SisNotConfiguredException;
use App\Jobs\SyncSchoolItem;
use App\Jobs\SyncSchools;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    logIn();
});

it('will redirect when no school is set', function () {});

it('syncs schools inline and throws when the sync finds none', function () {
    Bus::fake();
    $this->tenant->schools()->delete();

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('select-school')))->toThrow(SisNotConfiguredException::class);
    Bus::assertDispatchedSync(SyncSchools::class);
});

it('does not resync when every school is inactive', function () {
    Bus::fake();
    $this->tenant->schools()->update(['active' => false]);

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('select-school')))->toThrow(SisNotConfiguredException::class);
    Bus::assertNotDispatchedSync(SyncSchools::class);
});

it('does not throw for a guardian with no linked students', function () {
    $this->user->update(['user_type' => UserType::guardian]);

    $this->withoutExceptionHandling()
        ->get(route('select-school'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SchoolSelection')
            ->has('schools', 0)
        );
});

it('auto-selects the school when only one is available', function () {
    $school = $this->tenant->schools->first();
    $this->tenant->schools()->whereKeyNot($school->id)->delete();

    $this->get(route('select-school'))
        ->assertRedirect(route('home'));

    expect($this->user->refresh()->school_id)->toBe($school->id);
});

it('can view the school selection page', function () {
    $this->get(route('select-school'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SchoolSelection')
            ->has('schools')
            ->has('title')
            ->where('endpoint', route('select-school'))
        );
});

it("'can update a user's school'", function () {
    $school = $this->tenant->schools->random();

    $this->post(route('select-school'), [
        'school_id' => $school->id,
    ])
        ->assertSessionHas('success')
        ->assertRedirect(route('home'));

    $this->user->refresh();
    expect($this->user->school_id)->toBe($school->id);
});

it('starts the school sync and redirects to tenant settings on the first sync after onboarding', function () {
    Bus::fake();
    $school = $this->tenant->schools->random();

    $this->withSession(['first_sync' => true])
        ->post(route('select-school'), ['school_id' => $school->id])
        ->assertSessionMissing('first_sync')
        ->assertRedirect(route('settings.tenant.edit'));

    Bus::assertDispatched(SyncSchoolItem::class, fn (SyncSchoolItem $job) => $job->school->is($school)
        && $job->item === 'school'
        && $job->user->is($this->user));
});

it('flags the first sync when schools are synced inline', function () {
    Bus::fake();
    $this->tenant->schools()->delete();

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('select-school')))->toThrow(SisNotConfiguredException::class);
    expect(session('first_sync'))->toBeTrue();
});

it('does not start a school sync on later school selections', function () {
    Bus::fake();
    $school = $this->tenant->schools->random();

    $this->post(route('select-school'), ['school_id' => $school->id])
        ->assertRedirect(route('home'));

    Bus::assertNotDispatched(SyncSchoolItem::class);
});

it('sends the user to school selection when their current school is inactive', function () {
    setSchool();
    $this->school->update(['active' => false]);

    $this->get(route('home'))
        ->assertRedirect(route('select-school'));
});

it('rejects selecting an inactive school', function () {
    $school = $this->tenant->schools->first();
    $school->update(['active' => false]);

    $this->post(route('select-school'), ['school_id' => $school->id])
        ->assertSessionHasErrors('school_id');

    expect($this->user->fresh()->school_id)->not->toBe($school->id);
});

it('offers every school with the limit when onboarding a cloud license that covers fewer schools', function () {
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    $this->tenant->schools()->update(['active' => false]);

    $this->withSession(['first_sync' => true])
        ->get(route('select-school'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SchoolSelection')
            ->has('schools', $this->tenant->schools()->count())
            ->where('schoolLimit', 1)
        );
});

it('activates the chosen schools and selects the current school when onboarding within the limit', function () {
    Bus::fake();
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    $this->tenant->schools()->update(['active' => false]);
    [$chosen, $other] = $this->tenant->schools;

    $this->withSession(['first_sync' => true])
        ->post(route('select-school'), ['schools' => [$chosen->id], 'school_id' => $chosen->id])
        ->assertRedirect(route('settings.tenant.edit'));

    expect($chosen->fresh()->active)->toBeTrue()
        ->and($other->fresh()->active)->toBeFalse()
        ->and($this->user->fresh()->school_id)->toBe($chosen->id);
});

it('rejects onboarding with more schools than the limit or a current school outside the chosen ones', function () {
    $this->asCloud();
    $this->tenant->update(['school_limit' => 1]);
    $this->tenant->schools()->update(['active' => false]);
    [$chosen, $other] = $this->tenant->schools;

    $this->withSession(['first_sync' => true])
        ->post(route('select-school'), ['schools' => [$chosen->id, $other->id], 'school_id' => $chosen->id])
        ->assertSessionHasErrors('schools');

    $this->withSession(['first_sync' => true])
        ->post(route('select-school'), ['schools' => [$chosen->id], 'school_id' => $other->id])
        ->assertSessionHasErrors('school_id');

    expect($this->tenant->schools()->where('active', true)->exists())->toBeFalse();
});
