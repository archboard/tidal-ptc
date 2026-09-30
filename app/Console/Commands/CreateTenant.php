<?php

namespace App\Console\Commands;

use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create
        {name : The district name}
        {domain : The domain the district is served on}
        {--custom-domain= : An additional custom domain}
        {--license= : The license UUID, generated when omitted}
        {--expires= : When the subscription expires, defaults to a year from now}';

    protected $description = 'Create a cloud tenant and print its setup links';

    public function handle(): int
    {
        if (! config('app.cloud')) {
            $this->error('This command is only available in the cloud version of the app.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $this->argument('name'),
            'domain' => $this->argument('domain'),
            'custom_domain' => $this->option('custom-domain'),
            'license' => $this->option('license') ?? Str::uuid()->toString(),
            'subscription_started_at' => now()->toDateTimeString(),
            'subscription_expires_at' => $this->option('expires') ?? now()->addYear()->toDateTimeString(),
        ], (new StoreTenantRequest)->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $tenant = Tenant::create($validator->validated());

        $links = $tenant->setupLinks();
        $this->info("Created tenant {$tenant->id}.");
        $this->components->twoColumnDetail('Setup link', $links['setup_url']);
        $this->components->twoColumnDetail('Plugin link', $links['plugin_url']);

        return self::SUCCESS;
    }
}
