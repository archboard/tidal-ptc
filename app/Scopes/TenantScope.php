<?php

namespace App\Scopes;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $tenant = Tenant::current();

        if ($tenant) {
            $builder->where($model->getTable().'.tenant_id', $tenant->id);
        }
    }

    public function extend(Builder $builder)
    {
        $this->addWithoutTenant($builder);
    }

    protected function addWithoutTenant(Builder $builder)
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
