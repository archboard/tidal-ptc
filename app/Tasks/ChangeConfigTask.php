<?php

namespace App\Tasks;

use App\Http\Resources\TenantResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ChangeConfigTask implements SwitchTenantTask
{
    private string $originalUrl;

    public function makeCurrent(Tenant $tenant): void
    {
        $this->originalUrl = config('app.url');

        Config::set('app.url', "https://{$tenant->domain}");
        URL::forceRootUrl(config('app.url'));

        if (config('app.self_hosted')) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.from.address', $tenant->getConfigFieldValue('smtp_config', 'from_address'));
            Config::set('mail.from.name', $tenant->getConfigFieldValue('smtp_config', 'from_name'));
            Config::set('mail.mailers.smtp.host', $tenant->getConfigFieldValue('smtp_config', 'host'));
            Config::set('mail.mailers.smtp.port', $tenant->getConfigFieldValue('smtp_config', 'port'));
            Config::set('mail.mailers.smtp.encryption', $tenant->getConfigFieldValue('smtp_config', 'encryption'));
            Config::set('mail.mailers.smtp.username', $tenant->getConfigFieldValue('smtp_config', 'username'));
            Config::set('mail.mailers.smtp.password', $tenant->getConfigFieldValue('smtp_config', 'password'));
        }

        Inertia::share('tenant', fn () => new TenantResource($tenant));
    }

    public function forgetCurrent(): void
    {
        Config::set('app.url', $this->originalUrl);

        URL::forceRootUrl($this->originalUrl);
    }
}
