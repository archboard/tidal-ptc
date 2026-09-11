<?php

namespace App\Scopes;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = Tenant::current();

        if ($tenant) {
            $builder->where($model->getTable().'.tenant_id', $tenant->id);
        }
    }

    /** @param Builder<Model> $builder */
    public function extend(Builder $builder): void
    {
        $this->addWithoutTenant($builder);
    }

    /** @param Builder<Model> $builder */
    protected function addWithoutTenant(Builder $builder): void
    {
        $scope = $this;

        $builder->macro('withoutTenant', function (Builder $builder) use ($scope) {
            return $builder->withoutGlobalScope($scope);
        });
    }
}
