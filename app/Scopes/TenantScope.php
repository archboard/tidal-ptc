<?php

namespace App\Scopes;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    /**
     * Fails closed: a query without a current tenant would return every district's rows,
     * so it throws unless the caller opts out with `withoutTenant()`.
     *
     * @throws NoCurrentTenant
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = Tenant::current() ?? throw NoCurrentTenant::make();

        $builder->where($model->getTable().'.tenant_id', $tenant->id);
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
