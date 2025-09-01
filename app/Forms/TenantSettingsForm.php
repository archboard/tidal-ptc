<?php

namespace App\Forms;

use App\Enums\FieldType;
use App\Enums\Sis;
use App\Fields\FormField;
use App\Fields\FormFieldCollection;
use App\Forms\Traits\ValidatesTenantFields;
use App\Models\Tenant;

class TenantSettingsForm extends BaseForm
{
    use ValidatesTenantFields;

    public function __construct(protected Tenant $tenant) {}

    public function title(): string
    {
        return __('Tenant Settings');
    }

    public function method(): string
    {
        return 'put';
    }

    public function endpoint(): string
    {
        return route('settings.tenant.update');
    }

    public function fields(): FormFieldCollection
    {
        return FormFieldCollection::make([
            'name' => FormField::make(__('Tenant name'))
                ->withValue($this->tenant->name)
                ->help(__('IP address or domain name of the SMTP server.'))
                ->rules($this->nameRules()),
            'domain' => FormField::make(__('Domain'))
                ->withValue($this->tenant->domain)
                ->disabled(config('app.cloud'))
                ->span(3)
                ->rules($this->domainRules($this->tenant)),
            'sis_provider' => FormField::make(__('SIS data provider'))
                ->withValue($this->tenant->sis_provider->value)
                ->component(FieldType::select)
                ->span(3)
                ->withOptions(Sis::selectOptions())
                ->rules($this->sisProviderRules()),
            'allow_password_auth' => FormField::make(__('Allow password authentication'))
                ->withValue($this->tenant->allow_password_auth)
                ->component(FieldType::checkbox)
                ->help(__('Allow users to login with their email and password.'))
                ->rules(['boolean']),
            'allow_oidc_login' => FormField::make(__('Allow OpenID Connect login'))
                ->withValue($this->tenant->allow_oidc_login)
                ->component(FieldType::checkbox)
                ->help(__('Allow users to login with OpenID Connect with the SIS.'))
                ->rules(['boolean']),
        ])
            ->map(
                fn (FormField $field, string $key) => $field
                    ->keyedBy($key)
            );
    }
}
