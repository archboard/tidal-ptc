<?php

namespace App;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $model = $this->getTenantModel();

        return config('app.cloud')
            ? $model::fromRequest($request)
            : $model::first();
    }
}
