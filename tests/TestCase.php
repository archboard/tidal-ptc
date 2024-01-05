<?php

namespace Tests;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\School;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Silber\Bouncer\BouncerFacade;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $signIn = false;

    protected bool $cloud = false;

    protected Tenant $tenant;

    protected School $school;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $domain = env('TESTING_APP_URL');

        $this->tenant = Tenant::factory()->create(compact('domain'));
        $this->tenant->domain = $domain;
        $this->tenant->makeCurrent();
        School::factory()
            ->count(2)
            ->create(['tenant_id' => $this->tenant->id]);

        if ($this->signIn) {
            $this->logIn();
        }

        if ($this->cloud) {
            $this->asCloud();
        } else {
            $this->asSelfHosted();
        }

        BouncerFacade::scope()->remove();
    }

    public function seedUser(array $attributes = []): User
    {
        $tenant = $this->tenant ?? Tenant::factory()->create();
        $mergedAttributes = array_merge(['tenant_id' => $tenant->id], $attributes);
        $user = User::factory()->create($mergedAttributes);

        if (isset($this->school)) {
            $user->update(['school_id' => $this->school->id]);
            $user->schools()->attach($this->school->id);
        }

        return $user;
    }

    public function logIn(?User $user = null, array $attributes = [], Role $role = Role::DISTRICT_ADMIN): static
    {
        /** @var User $user */
        $user = $user ?? $this->seedUser($attributes);

//        $user->assign($role->value);
        $this->be($user);
        $this->user = $user;

        return $this;
    }

    public function fullPermission(): static
    {
        BouncerFacade::allow($this->user)->everything();

        return $this;
    }

    public function givePermission(Permission $permission): static
    {
        $scope = $permission->shouldBeScoped()
            ? $this->user->school_id
            : null;

        BouncerFacade::scope()
            ->onceTo($scope, fn () => BouncerFacade::allow($this->user)->to($permission->value));

        return $this;
    }

    public function setSchool(): static
    {
        $this->school = $this->tenant->schools->random();
        $this->user->schools()->sync($this->school);
        $this->user->update([
            'school_id' => $this->school->id,
        ]);

        return $this;
    }

    public function asCloud(): static
    {
        config()->set('app.cloud', true);
        config()->set('app.self_hosted', false);

        return $this;
    }

    public function asSelfHosted(): static
    {
        config()->set('app.cloud', false);
        config()->set('app.self_hosted', true);

        return $this;
    }

    public function tapUser(callable $callback): static
    {
        $callback($this->user);

        return $this;
    }
}
