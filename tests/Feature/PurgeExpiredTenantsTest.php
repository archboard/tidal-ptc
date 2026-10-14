<?php

use App\Console\Commands\PurgeExpiredTenants;
use App\Enums\Role;
use App\Models\School;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;

beforeEach(function () {
    $this->asCloud();
    Tenant::forgetCurrent();
});

it('permanently deletes an expired tenant and everything it owns', function () {
    $tenant = Tenant::factory()->create(['subscription_expires_at' => now()->subDay()]);
    $school = School::factory()->for($tenant)->create();
    $user = $tenant->execute(function () use ($tenant, $school) {
        $user = seedUser(['tenant_id' => $tenant->id, 'school_id' => $school->id]);
        $user->assign(Role::DISTRICT_ADMIN->value);
        BouncerFacade::allow($user)->to('edit-settings');
        DB::table('school_languages')->insert(['school_id' => $school->id, 'language' => 'es']);
        DB::table('sessions')->insert(['id' => 'expired', 'user_id' => $user->id, 'payload' => '', 'last_activity' => 0]);
        DB::table('activity_log')->insert(['tenant_id' => $tenant->id, 'description' => 'Updated']);
        DB::table('activity_log')->insert(['subject_type' => 'tenant', 'subject_id' => $tenant->id, 'description' => 'Updated']);

        return $user;
    });

    $this->artisan(PurgeExpiredTenants::class)->assertSuccessful();

    $this->assertModelMissing($tenant);
    $this->assertDatabaseMissing('schools', ['tenant_id' => $tenant->id]);
    $this->assertDatabaseMissing('users', ['tenant_id' => $tenant->id]);
    $this->assertDatabaseMissing('school_languages', ['school_id' => $school->id]);
    $this->assertDatabaseMissing('sessions', ['id' => 'expired']);
    $this->assertDatabaseMissing('activity_log', ['tenant_id' => $tenant->id]);
    $this->assertDatabaseMissing('activity_log', ['subject_type' => 'tenant', 'subject_id' => $tenant->id]);
    $this->assertDatabaseMissing('assigned_roles', ['entity_type' => 'user', 'entity_id' => $user->id]);
    $this->assertDatabaseMissing('permissions', ['entity_type' => 'user', 'entity_id' => $user->id]);
});

it('keeps tenants whose subscription has not expired', function () {
    $active = Tenant::factory()->create(['subscription_expires_at' => now()->addDay()]);
    $user = $this->tenant->execute(fn () => seedUser());

    $this->artisan(PurgeExpiredTenants::class)->assertSuccessful();

    $this->assertModelExists($active);
    $this->assertModelExists($this->tenant);
    $this->assertModelExists($user);
});

it('refuses to run when self-hosted', function () {
    $tenant = Tenant::factory()->create(['subscription_expires_at' => now()->subDay()]);

    $this->asSelfHosted()
        ->artisan(PurgeExpiredTenants::class)
        ->assertFailed();

    $this->assertModelExists($tenant);
});
