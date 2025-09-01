<?php

namespace Tests;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Course;
use App\Models\School;
use App\Models\Section;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Uri;
use Silber\Bouncer\BouncerFacade;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $signIn = false;

    protected bool $cloud = false;

    public Tenant $tenant;

    public School $school;

    public ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $domain = Uri::of(env('APP_URL'))->host();

        $this->tenant = Tenant::factory()->create(compact('domain'));
        $this->tenant->makeCurrent();
        School::factory(2)
            ->for($this->tenant)
            ->create();

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

    public function givePermission(Permission $permission, $arguments = null): static
    {
        $scope = $permission->shouldBeScoped()
            ? $this->user->school_id
            : null;

        BouncerFacade::scope()
            ->onceTo($scope, fn () => BouncerFacade::allow($this->user)->to($permission->value, $arguments));

        return $this;
    }

    public function setSchool(): static
    {
        $this->school = $this->tenant->schools->random();

        if (isset($this->user)) {
            $this->user->schools()->sync($this->school);
            $this->user->update([
                'school_id' => $this->school->id,
            ]);
        }

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

    public function seedSection(): Section
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'school_id' => $this->school->id,
        ]);

        return $course->sections()
            ->save(Section::factory()->make([
                'tenant_id' => $this->tenant->id,
                'school_id' => $this->school->id,
                'user_id' => $this->seedUser()->id,
            ]));
    }

    public function seedTimeSlot(array $attributes = [], ?User $user = null): TimeSlot
    {
        $user = $user ?? $this->user ?? $this->seedUser();

        return $user->timeSlots()
            ->save(TimeSlot::factory()->make($attributes));
    }
}
