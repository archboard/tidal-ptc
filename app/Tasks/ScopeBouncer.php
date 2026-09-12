<?php

namespace App\Tasks;

use App\Models\Tenant;
use Silber\Bouncer\BouncerFacade;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ScopeBouncer implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        /** @var Tenant $tenant */
        BouncerFacade::scope()->to($tenant->id);
    }

    public function forgetCurrent(): void
    {
        BouncerFacade::scope()
            ->remove();
    }
}
