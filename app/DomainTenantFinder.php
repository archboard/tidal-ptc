<?php

namespace App;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $model = $this->getTenantModel();

        return config('app.cloud')
            ? $model::fromRequest($request)
            : $model::first();
    }
}
