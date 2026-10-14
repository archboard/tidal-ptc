<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class PurgeExpiredTenants extends Command
{
    protected $signature = 'tenant:purge-expired';

    protected $description = 'Permanently delete cloud tenants whose subscription has expired';

    public function handle(): int
    {
        if (! config('app.cloud')) {
            $this->error('This command is only available in the cloud version of the app.');

            return self::FAILURE;
        }

        $tenants = Tenant::query()
            ->where('subscription_expires_at', '<', now())
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->purge();
            $this->info("Purged {$tenant->name} ({$tenant->id}).");
        }

        return self::SUCCESS;
    }
}
