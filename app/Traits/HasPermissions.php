<?php

namespace App\Traits;

use App\Enums\Permission;
use App\Exceptions\InvalidPermissionException;
use App\Models\Contracts\ExistsInSis;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade;

trait HasPermissions
{
    public function permissions(): Attribute
    {
        return Attribute::get(
            fn () => Cache::rememberForever(
                $this->permissionsCacheKey(),
                fn () => $this->permissionsToFrontend(app(School::class))
            )
        );
    }

    protected function permissionsCacheKey(): string
    {
        return $this->sis_key.'|permissions';
    }

    public function clearPermissionCache(): static
    {
        BouncerFacade::refreshFor($this);
        Cache::forget($this->permissionsCacheKey());

        return $this;
    }

    public function getPermissionSubjectModels(): array
    {
        return [
            \App\Models\User::class,
            \App\Models\Course::class,
            \App\Models\Section::class,
            \App\Models\Student::class,
        ];
    }

    public function getPermissionMatrix(?User $authUser = null, ?School $school = null): array
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
                return (! $authUser || $authUser->can('*'))
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

                $this->clearPermissionCache();
            });

        return $this;
    }

    public function permissionsToFrontend(School $school): array
    {
        $matrix = $this->getPermissionMatrix(school: $school);
        $abilities = collect($matrix['permissions'])
            ->mapWithKeys(fn (array $permission) => [$permission['key'] => $permission['granted']]);
        $permissions = collect(Arr::get($matrix, 'schools.'.$school->id.'.permissions', []))
            ->mapWithKeys(fn (array $permission) => [$permission['key'] => $permission['granted']]);
        $models = collect(Arr::get($matrix, 'schools.'.$school->id.'.models', []))
            ->mapWithKeys(fn (array $model) => [
                $model['model'] => collect($model['permissions'])
                    ->mapWithKeys(fn (array $permission) => [$permission['key'] => $permission['granted']]),
            ]);

        return $abilities->merge($permissions)
            ->merge($models)
            ->toArray();
    }

    public function hasCachedPermission(string|Permission $model, ?Permission $permission = null): bool
    {
        $key = ($model?->value ?? $model).($permission ? '.'.$permission->key() : '');

        return Arr::get($this->permissions, $key, false);
    }
}
