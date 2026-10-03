<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class IssueTenantSetupLinks extends Command
{
    protected $signature = 'tenant:setup-link {tenant : The tenant id or domain}';

    protected $description = 'Print fresh setup links for a cloud tenant that has no district admin yet';

    public function handle(): int
    {
        if (! config('app.cloud')) {
            $this->error('This command is only available in the cloud version of the app.');

            return self::FAILURE;
        }

        $identifier = $this->argument('tenant');
        $tenant = ctype_digit($identifier)
            ? Tenant::find($identifier)
            : Tenant::getByHost($identifier);

        if (! $tenant) {
            $this->error("No tenant found for {$identifier}.");

            return self::FAILURE;
        }

        if ($tenant->execute(fn (Tenant $tenant) => $tenant->hasDistrictAdmin())) {
            $this->error("{$tenant->name} already has a district admin, so setup is closed.");

            return self::FAILURE;
        }

        $links = $tenant->setupLinks();
        $this->components->twoColumnDetail('Setup link', $links['setup_url']);
        $this->components->twoColumnDetail('Plugin link', $links['plugin_url']);

        return self::SUCCESS;
    }
}
