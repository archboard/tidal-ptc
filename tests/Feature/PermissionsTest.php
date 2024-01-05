<?php

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Relations\Relation;

beforeEach(function () {
    logIn()->setSchool();

    $this->subjectUser = test()->seedUser();
});

it("can't view without permission", function () {
    $this->get(route('users.permissions.index', $this->subjectUser))
        ->assertForbidden();
});

it('can view with permission', function () {
    givePermission(Permission::editPermissions);

    $this->get(route('users.permissions.index', $this->subjectUser))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->has('title')
            ->has('subject')
        );
});

it("can't update without permission", function () {
    $this->put(route('users.permissions.update', $this->subjectUser))
        ->assertForbidden();
});

it('can give permissions with the right permission', function (array $data) {
    $this->put(route('users.permissions.update', $this->subjectUser), $data)
        ->assertSessionHasNoErrors()
        ->assertOk();

    $permission = Permission::from($data['permission']);
    $assertion = $data['granted'] ? 'assertTrue' : 'assertFalse';
    $model = Relation::getMorphedModel($data['model']);

    $this->$assertion(
        Bouncer::scope()
            ->onceTo($data['school'], fn () => $this->subjectUser->can($data['permission'], $model))
    );

    if ($permission->shouldBeScoped()) {
        $this->assertTrue(
            Bouncer::scope()
                ->removeOnce(fn () => $this->subjectUser->cant($data['permission'], $model))
        );
    }

    $everythingAssertion = $data['granted'] && $permission === Permission::everything
        ? 'assertTrue'
        : 'assertFalse';
    if ($permission === Permission::everything) {
        $this->$everythingAssertion(
            Bouncer::scope()
                ->onceTo($data['school'], fn () => $this->subjectUser->can('do anything', $model))
        );
        $this->$assertion(
            Bouncer::scope()
                ->onceTo($data['school'], fn () => $this->subjectUser->can(Permission::viewAny->value, \App\Models\Course::class))
        );

        if ($data['school']) {
            $this->$everythingAssertion(
                Bouncer::scope()
                    ->removeOnce(fn () => $this->subjectUser->cant('do anything', $model))
            );
        }
    }
})->with([
    'full access granting tenant settings access' => function () {
        Bouncer::allow($this->user)->everything();

        return [
            'permission' => Permission::editTenantSettings->value,
            'granted' => true,
            'school' => null,
            'model' => null,
        ];
    },
    'full access granting full access' => function () {
        Bouncer::allow($this->user)->everything();

        return [
            'permission' => Permission::everything->value,
            'granted' => true,
            'school' => null,
            'model' => null,
        ];
    },
    'full access granting school full access' => function () {
        Bouncer::allow($this->user)->everything();

        return [
            'permission' => Permission::everything->value,
            'granted' => true,
            'school' => $this->user->school_id,
            'model' => '*',
        ];
    },
    'school full access giving full school access' => function () {
        Bouncer::scope()
            ->onceTo($this->user->school_id, fn () => Bouncer::allow($this->user)->everything());

        return [
            'permission' => Permission::everything->value,
            'granted' => true,
            'school' => $this->user->school_id,
            'model' => '*',
        ];
    },
    'school user permission giving full school access' => function () {
        Bouncer::scope()
            ->onceTo($this->user->school_id, fn () => Bouncer::allow($this->user)->to(Permission::editPermissions->value));

        return [
            'permission' => Permission::everything->value,
            'granted' => true,
            'school' => $this->user->school_id,
            'model' => '*',
        ];
    },
    'school user permission giving individual model access' => function () {
        Bouncer::scope()
            ->onceTo($this->user->school_id, fn () => Bouncer::allow($this->user)->to(Permission::editPermissions->value));

        return [
            'permission' => Permission::view->value,
            'granted' => true,
            'school' => $this->user->school_id,
            'model' => 'user',
        ];
    },
    'school user permission removing individual model access' => function () {
        Bouncer::scope()
            ->onceTo($this->user->school_id, fn () => Bouncer::allow($this->user)->to(Permission::editPermissions->value));

        return [
            'permission' => Permission::update->value,
            'granted' => false,
            'school' => $this->user->school_id,
            'model' => 'section',
        ];
    },
]);
