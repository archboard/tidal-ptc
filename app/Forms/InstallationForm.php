<?php

namespace App\Forms;

use App\Fields\FormField;
use App\Fields\FormFieldCollection;
use App\Forms\Contracts\AppForm;
use App\Forms\Traits\ValidatesTenantFields;
use App\Models\Tenant;

class InstallationForm implements AppForm
{
    use ValidatesTenantFields;

    public function __construct(protected Tenant $tenant)
    {
    }

    public function fields(): FormFieldCollection
    {
        return FormFieldCollection::make([
            'name' => FormField::make(__('Tenant name'))
                ->withValue($this->tenant->name)
                ->rules($this->nameRules()),
            'domain' => FormField::make(__('Domain'))
                ->withValue($this->tenant->domain)
                ->disabled(config('app.cloud'))
                ->rules($this->domainRules($this->tenant)),
            ...(config('app.cloud')
                ? ['custom_domain' => FormField::make(__('Custom domain'))
                    ->withValue($this->tenant->custom_domain)
                    ->rules($this->customDomainRules($this->tenant))]
                : []),
            'email' => FormField::make(__('Email'))
                ->help(__('This is the email address for a system admin and should match your email that is used in your SIS.'))
                ->rules($this->emailRules()),
            ...$this->tenant->sis_provider?->getConfigFields() ?? collect(),
        ])
            ->map(fn (FormField $field, string $key) => $field
                ->withValue($this->tenant->getInstallationFieldValue($key))
                ->keyedBy($key)
            );
    }
}
