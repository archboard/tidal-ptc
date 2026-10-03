<?php

namespace App\Console\Commands;

use App\Http\Requests\UpsertTenantRequest;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create
        {name : The district name}
        {domain : The domain the district is served on}
        {license : The license UUID from the billing portal}
        {schools : How many schools the license covers}
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
            'license' => $this->argument('license'),
            'school_limit' => $this->argument('schools'),
            'subscription_started_at' => now()->toDateTimeString(),
            'subscription_expires_at' => $this->option('expires') ?? now()->addYear()->toDateTimeString(),
        ], [
            ...UpsertTenantRequest::rulesFor(null),
            'license' => ['required', 'uuid', 'unique:tenants'],
        ]);

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
