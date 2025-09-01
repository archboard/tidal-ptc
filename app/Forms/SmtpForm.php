<?php

namespace App\Forms;

use App\Enums\FieldType;
use App\Fields\FormField;
use App\Fields\FormFieldCollection;
use App\Forms\Traits\ValidatesTenantFields;
use App\Models\Tenant;

class SmtpForm extends BaseForm
{
    use ValidatesTenantFields;

    public function __construct(protected Tenant $tenant) {}

    public function title(): string
    {
        return __('SMTP Settings');
    }

    public function method(): string
    {
        return 'put';
    }

    public function endpoint(): string
    {
        return route('settings.tenant.smtp');
    }

    public function fields(): FormFieldCollection
    {
        $rules = $this->smtpRules();

        return FormFieldCollection::make([
            'host' => FormField::make(__('Host'))
                ->span(3)
                ->placeholder('127.0.0.1')
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'host'))
                ->help(__('IP address or domain name of the SMTP server.'))
                ->rules($rules['host']),
            'port' => FormField::make(__('Port'))
                ->component(FieldType::number)
                ->span(3)
                ->placeholder('587')
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'port'))
                ->rules($rules['port']),
            'username' => FormField::make(__('Username'))
                ->span(3)
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'username'))
                ->rules($rules['username']),
            'password' => FormField::make(__('Password'))
                ->span(3)
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'password'))
                ->component(FieldType::input)
                ->rules($rules['password']),
            'from_name' => FormField::make(__('From name'))
                ->span(2)
                ->placeholder('App Name')
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'from_name'))
                ->rules($rules['from_name']),
            'from_address' => FormField::make(__('From address'))
                ->span(2)
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'from_address'))
                ->component(FieldType::email)
                ->rules($rules['from_address']),
            'encryption' => FormField::make(__('Encryption'))
                ->span(2)
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', 'encryption'))
                ->component(FieldType::select)
                ->withOptions([
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                ])
                ->rules($rules['encryption']),
        ])
            ->map(fn (FormField $field, string $key) => $field
                ->withValue($this->tenant->getConfigFieldValue('smtp_config', $key))
                ->keyedBy($key)
            );
    }
}
