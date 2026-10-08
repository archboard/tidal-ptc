<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserType;
use App\Models\School;
use App\Models\Section;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    config()->set('app.demo', true);
});

it('logs in as the demo user for each type', function (UserType $userType, string $email) {
    $user = seedUser(['email' => $email, 'user_type' => $userType]);

    $this->post(route('demo.login', $userType))
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
})->with([
    'admin' => [UserType::staff, DemoSeeder::ADMIN_EMAIL],
    'guardian' => [UserType::guardian, DemoSeeder::GUARDIAN_EMAIL],
]);

it('has no demo login outside demo mode', function () {
    config()->set('app.demo', false);
    seedUser(['email' => DemoSeeder::ADMIN_EMAIL, 'user_type' => UserType::staff]);

    $this->post(route('demo.login', UserType::staff))
        ->assertNotFound();

    $this->assertGuest();
});

it('blocks settings changes in demo mode', function () {
    logIn()->fullPermission();
    $name = $this->tenant->name;

    $this->from(route('settings.tenant.edit'))
        ->put(route('settings.tenant.update'), ['name' => 'Changed', 'sis_provider' => $this->tenant->sis_provider->value])
        ->assertRedirect(route('settings.tenant.edit'))
        ->assertSessionHas('error', 'This is disabled in demo mode.');

    expect($this->tenant->refresh()->name)->toBe($name);
});

it('marks settings pages read-only in demo mode', function () {
    logIn()->fullPermission();

    $this->get(route('settings.tenant.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('demo', true)
            ->where('demoReadOnly', true)
        );
});

it('still allows creating time slots in demo mode', function () {
    logIn()->setSchool();
    $this->school->update(['open_for_teachers_at' => now()->subDay()]);
    $teacher = seedUser();

    $this->givePermission(Permission::create, TimeSlot::class)
        ->post(route('time-slots.store'), makeTimeSlotRequest(['user_id' => $teacher->id]))
        ->assertOk();

    expect($teacher->timeSlots()->count())->toBe(1);
});

it('seeds the demo district', function () {
    config()->set('app.url', 'https://demo.example.com');

    $this->seed(DemoSeeder::class);

    $tenant = Tenant::current();
    $admin = User::query()->where('email', DemoSeeder::ADMIN_EMAIL)->sole();
    $guardian = User::query()->where('email', DemoSeeder::GUARDIAN_EMAIL)->sole();
    $teachers = User::query()->where('tenant_id', $tenant->id)->whereHas('sections')->get();

    expect($tenant->domain)->toBe('demo.example.com')
        ->and($tenant->installed())->toBeTrue()
        ->and(School::query()->where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and($tenant->courses()->count())->toBe(24)
        ->and(Section::query()->where('tenant_id', $tenant->id)->whereNotNull('alt_user_id')->count())->toBe(6)
        ->and($admin->isA(Role::DISTRICT_ADMIN->value))->toBeTrue()
        ->and($admin->can(Permission::editTenantSettings->value))->toBeTrue()
        ->and($admin->schools()->count())->toBe(2)
        ->and($guardian->user_type)->toBe(UserType::guardian)
        ->and($guardian->students()->distinct()->count('school_id'))->toBe(2)
        ->and($guardian->students()->count())->toBe(3)
        ->and($teachers)->toHaveCount(16)
        ->and($teachers->map(fn (User $teacher) => $teacher->timeSlots()->count())->unique()->all())->toBe([40]);
});
