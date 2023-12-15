<?php

namespace App\Tasks;

use Silber\Bouncer\BouncerFacade;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ScopeBouncer implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        /** @var \App\Models\Tenant $tenant */
        BouncerFacade::scope()->to($tenant->id);
    }

    public function forgetCurrent(): void
    {
        BouncerFacade::scope()
            ->remove();
    }
}
