<?php

namespace App;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        /** @var class-string<\App\Models\Tenant> $model */
        $model = app(IsTenant::class);

        return config('app.cloud')
            ? $model::fromRequest($request)
            : $model::first();
    }
}
