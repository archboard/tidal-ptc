<?php

use App\Models\Activity;
use App\Models\School;
use App\Models\Tenant;
use Spatie\Activitylog\Commands\CleanActivitylogCommand;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

it('throws when a tenant model is queried without a current tenant', function () {
    Tenant::forgetCurrent();

    School::query()->get();
})->throws(NoCurrentTenant::class);

it('returns every tenant\'s rows with withoutTenant', function () {
    School::factory()->for(Tenant::factory())->create();
    Tenant::forgetCurrent();

    expect(School::withoutTenant()->count())->toBe(3);
});

it('cleans old activity for every tenant without a current tenant', function () {
    $other = Tenant::factory()->create();
    $old = Activity::withoutTenant()->create(['description' => 'old', 'tenant_id' => $other->id, 'created_at' => now()->subYears(2)]);
    $recent = Activity::withoutTenant()->create(['description' => 'recent', 'tenant_id' => $other->id]);
    Tenant::forgetCurrent();

    $this->artisan(CleanActivitylogCommand::class)->assertSuccessful();

    $this->assertModelMissing($old);
    $this->assertModelExists($recent);
});
