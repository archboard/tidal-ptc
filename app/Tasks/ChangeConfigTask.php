<?php

namespace App\Tasks;

use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ChangeConfigTask implements SwitchTenantTask
{
    private string $originalUrl;

    /** @var array<string, mixed> */
    private array $originalMail;

    /**
     * @param  Tenant  $tenant
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        $this->originalUrl = config('app.url');
        $this->originalMail = config('mail');

        Config::set('app.url', "https://{$tenant->domain}");
        URL::useOrigin(config('app.url'));

        $usesOwnSmtp = config('app.self_hosted') || $tenant->getConfigFieldValue('smtp_config', 'custom');

        if ($usesOwnSmtp && $tenant->getConfigFieldValue('smtp_config', 'host')) {
            // Long-running processes (queue workers, Octane) cache built mailers across tenants
            Mail::forgetMailers();
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

        URL::useOrigin($this->originalUrl);

        // Back to the app-wide mailer so the next tenant doesn't send with this one's SMTP
        Config::set('mail', $this->originalMail);
        Mail::forgetMailers();
    }
}
