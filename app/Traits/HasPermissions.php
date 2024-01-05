<?php

namespace App\Traits;

use App\Enums\Permission;
use App\Exceptions\InvalidPermissionException;
use App\Models\Contracts\ExistsInSis;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade;

trait HasPermissions
{
    public function getPermissionSubjectModels(): array
    {
        return [
            \App\Models\User::class,
            \App\Models\Course::class,
            \App\Models\Section::class,
            \App\Models\Student::class,
        ];
    }

    public function getPermissionMatrix(User $authUser, ?School $school = null): array
    {
        $schools = $school
            ? collect([$school])
            : School::query()
                ->active()
                ->where('tenant_id', $this->tenant_id)
                ->get();
        $schoolPermissions = $schools->mapWithKeys(
            fn (School $school) => [$school->id => $this->getScopedPermissionMatrix($school)]
        )->toArray();
        $permissions = BouncerFacade::scope()
            ->removeOnce(function () use ($authUser) {
                return $authUser->can('*')
                    ? Permission::getNonCrud()
                        ->filter(fn (Permission $permission) => ! $permission->shouldBeScoped())
                        ->map(fn (Permission $permission) => $permission->toEntry($this))
                        ->values()
                        ->toArray()
                    : [];
            });

        return [
            'permissions' => $permissions,
            'schools' => $schoolPermissions,
        ];
    }

    public function getScopedPermissionMatrix(School $school): array
    {
        return BouncerFacade::scope()
            ->onceTo($school->id, function () {
                $crudPermissions = Permission::getCrud()
                    ->filter(fn (Permission $permission) => $permission->shouldBeScoped());
                $sisCrudPermissions = $crudPermissions->filter(
                    fn (Permission $permission) => $permission->isSisCrud() && $permission->shouldBeScoped()
                );
                $modelPermissions = array_map(function (string $model) use ($crudPermissions, $sisCrudPermissions) {
                    /** @var Model $instance */
                    $instance = new $model();
                    $permissions = $instance instanceof ExistsInSis
                        ? $sisCrudPermissions
                        : $crudPermissions;

                    return [
                        'model' => $instance->getMorphClass(),
                        'label' => (string) Str::of($instance->getTable())
                            ->replace('_', ' ')
                            ->title(),
                        'manages' => $this->can('*', $model),
                        'permissions' => $permissions->map(
                            fn (Permission $permission) => $permission->toEntry($this, $model)
                        )->values()->toArray(),
                    ];
                }, $this->getPermissionSubjectModels());

                return [
                    'manages' => $this->can('*'),
                    'permissions' => Permission::getNonCrud()
                        ->filter(fn (Permission $permission) => $permission->shouldBeScoped())
                        ->map(fn (Permission $permission) => $permission->toEntry($this))
                        ->values()
                        ->toArray(),
                    'models' => $modelPermissions,
                ];
            });
    }

    public function updateAppPermission(Permission $permission, bool $granted, ?School $school = null, ?string $model = null): static
    {
        if ($permission->shouldBeScoped() && ! $school) {
            throw new InvalidPermissionException('This permission requires a school to be selected.');
        }

        BouncerFacade::scope()
            ->onceTo($school?->id, function () use ($permission, $granted, $model) {
                $action = $granted ? 'allow' : 'disallow';
                if ($model && ! class_exists($model)) {
                    $model = Relation::getMorphedModel($model);
                }

                if ($permission === Permission::everything) {
                    if ($model) {
                        BouncerFacade::$action($this)->toManage($model);
                    } else {
                        BouncerFacade::$action($this)->everything();
                    }
                } else {
                    $this->$action($permission->value, $model);
                }

                BouncerFacade::refreshFor($this);
            });

        ray($this->getAbilities());

        return $this;
    }
}
