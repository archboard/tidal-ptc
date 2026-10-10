<?php

namespace App\Forms\Traits;

use App\Models\Tenant;
use App\Rules\ValidLicense;
use Illuminate\Validation\Rule;

trait ValidatesTenantFields
{
    /** @return array<int, mixed> */
    public function licenseRules(Tenant $tenant): array
    {
        return [
            'required',
            'uuid',
            new ValidLicense,
            Rule::unique('tenants', 'license')->ignoreModel($tenant),
        ];
    }

    /** @return array<int, mixed> */
    public function sisProviderRules(): array
    {
        return ['required'];
    }

    /** @return array<int, mixed> */
    public function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /** @return array<int, mixed> */
    public function domainRules(Tenant $tenant): array
    {
        return [
            'required',
            Rule::unique('tenants', 'domain')->ignoreModel($tenant),
        ];
    }

    /** @return array<int, mixed> */
    public function customDomainRules(Tenant $tenant): array
    {
        return [
            'nullable',
            Rule::unique('tenants', 'domain')->ignoreModel($tenant),
            Rule::unique('tenants', 'custom_domain')->ignoreModel($tenant),
        ];
    }

    /** @return array<int, mixed> */
    public function emailRules(): array
    {
        return [
            'required',
            'email',
        ];
    }

    /**
     * Cloud tenants send through the app-wide mailer unless they opt into their own provider.
     *
     * @return array<string, mixed>
     */
    public function smtpRules(): array
    {
        $required = config('app.cloud') ? 'required_if_accepted:custom' : 'required';

        return [
            ...(config('app.cloud') ? ['custom' => ['required', 'boolean']] : []),
            'host' => [$required],
            'port' => [$required],
            'username' => ['nullable'],
            'password' => ['nullable'],
            'from_name' => [$required],
            'from_address' => [$required, 'nullable', 'email'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
        ];
    }
}
