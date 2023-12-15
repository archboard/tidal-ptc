<?php

namespace Tests;

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
    }

    public function seedUser(array $attributes = []): User
    {
        $tenant = $this->tenant ?? Tenant::factory()->create();
        $mergedAttributes = array_merge(['tenant_id' => $tenant->id], $attributes);

        return User::factory()->create($mergedAttributes);
    }

    public function logIn(?User $user = null, array $attributes = [], Role $role = Role::DISTRICT_ADMIN): static
    {
        /** @var User $user */
        $user = $user ?? $this->seedUser($attributes);

        $user->assign($role->value);
        $this->be($user);
        $this->user = $user;

        return $this;
    }

    public function fullPermission(): static
    {
        BouncerFacade::allow($this->user)->everything();

        return $this;
    }

    public function setSchool(): static
    {
        $school = $this->tenant->schools()->save(
            School::factory()->make()
        );
        $this->user->schools()->attach($school);
        $this->user->update([
            'school_id' => $school->id,
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
