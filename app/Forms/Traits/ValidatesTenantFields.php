<?php

namespace App\Forms\Traits;

use App\Models\Tenant;
use App\Rules\ValidLicense;
use Illuminate\Validation\Rule;

trait ValidatesTenantFields
{
    public function licenseRules(Tenant $tenant): array
    {
        return [
            'required',
            'uuid',
            new ValidLicense,
            Rule::unique('tenants', 'license')->ignoreModel($tenant),
        ];
    }

    public function sisProviderRules(): array
    {
        return ['required'];
    }

    public function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    public function domainRules(Tenant $tenant): array
    {
        return [
            'required',
            Rule::unique('tenants', 'domain')->ignoreModel($tenant),
        ];
    }

    public function customDomainRules(Tenant $tenant): array
    {
        return [
            'nullable',
            Rule::unique('tenants', 'domain')->ignoreModel($tenant),
            Rule::unique('tenants', 'custom_domain')->ignoreModel($tenant),
        ];
    }

    public function emailRules(): array
    {
        return [
            'required',
            'email',
        ];
    }

    public function smtpRules(): array
    {
        return [
            'host' => ['required'],
            'port' => ['required'],
            'username' => ['nullable'],
            'password' => ['nullable'],
            'from_name' => ['required'],
            'from_address' => ['required', 'email'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
        ];
    }
}
