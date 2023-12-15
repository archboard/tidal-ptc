<?php

namespace App\Models;

use App\Enums\Sis;
use App\Fields\FormField;
use App\Fields\FormFieldCollection;
use App\SisProviders\SisProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Multitenancy\Models\Tenant as TenantBase;

/**
 * @mixin IdeHelperTenant
 */
class Tenant extends TenantBase
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sis_provider' => Sis::class,
        'sis_config' => 'encrypted:collection',
        'smtp_config' => 'encrypted:collection',
        'allow_password_auth' => 'boolean',
    ];

    protected static function booted()
    {
        static::created(function (Tenant $tenant) {
            //
        });
    }

    public function domain(): Attribute
    {
        return Attribute::get(fn ($value) => $value ?? request()->host());
    }

    public function sisConfig(): Attribute
    {
        return Attribute::get(
            fn ($value): Collection => $value ? $this->castAttribute('sis_config', $value) : collect()
        );
    }

    public function smtpConfig(): Attribute
    {
        return Attribute::get(function ($value): Collection {
            return $value
                ? $this->castAttribute('smtp_config', $value)
                : collect([
                    'host' => null,
                    'port' => null,
                    'username' => null,
                    'password' => null,
                    'from_name' => null,
                    'from_address' => null,
                    'encryption' => null,
                ]);
        });
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public static function fromRequest(Request $request): ?Tenant
    {
        return static::getByHost($request->getHost());
    }

    public static function fromRequestAndFallback(Request $request): Tenant
    {
        return static::fromRequest($request) ?? new static([
            'domain' => $request->getHost(),
            'sis_provider' => Sis::PS,
        ]);
    }

    public static function getByHost(string $host): ?Tenant
    {
        return static::query()
            ->where('domain', $host)
            ->orWhere(function (Builder $builder) use ($host) {
                $builder->whereNotNull('custom_domain')
                    ->where('custom_domain', $host);
            })
            ->first();
    }

    public function installed(): bool
    {
        return $this->getSisProvider()?->configured() ?? false;
    }

    public function getSisProvider(): ?SisProvider
    {
        return $this->sis_provider?->getProvider($this);
    }

    public function getSchoolFromSisId($sisId): School
    {
        if ($sisId instanceof School) {
            return $sisId;
        }

        /** @var School $school */
        $school = $this->schools()
            ->where('sis_id', $sisId)
            ->firstOrFail();

        return $school;
    }

    public function getConfigKey(string $configKey, ?string $key, mixed $defaultValue = null): mixed
    {
        return Arr::get(
            $this->$configKey,
            Str::replace("{$configKey}.", '', $key),
            $defaultValue
        );
    }

    public function setConfigKey(string $configKey, ?string $key, mixed $value): static
    {
        $this->$configKey = [
            ...$this->$configKey,
            Str::replace("{$configKey}.", '', $key) => $value,
        ];

        return $this;
    }

    public function getInstallationFieldValue(?string $key): mixed
    {
        return $this->getConfigFieldValue('sis_config', $key);
    }

    public function getConfigFieldValue(string $configKey, ?string $key = null): mixed
    {
        if (! $key && Str::contains($configKey, '.')) {
            [$configKey, $key] = explode('.', $configKey);

            return $this->getConfigFieldValue($configKey, $key);
        }

        return $this->$configKey->get($key);
    }

    public function getInstallationFields(): FormFieldCollection
    {
        return FormFieldCollection::make([
            'name' => FormField::make(__('Tenant name'))
                ->rules(['required', 'string', 'max:255']),
            'domain' => FormField::make(__('Domain'))
                ->disabled(config('app.cloud'))
                ->rules([
                    'required',
                    Rule::unique('tenants', 'domain')->ignoreModel($this),
                ]),
            ...(config('app.cloud')
                ? ['custom_domain' => FormField::make(__('Custom domain'))
                    ->rules([
                        'nullable',
                        Rule::unique('tenants', 'domain')->ignoreModel($this),
                        Rule::unique('tenants', 'custom_domain')->ignoreModel($this),
                    ])]
                : []),
            'email' => FormField::make(__('Email'))
                ->help(__('This is the email address for a system admin and should match your email that is used in your SIS.'))
                ->rules([
                    'required',
                    'email',
                ]),
            ...$this->sis_provider?->getConfigFields() ?? collect(),
        ])
            ->map(fn (FormField $field, string $key) => $field
                ->withValue($this->getInstallationFieldValue($key) ?? $this->getAttribute($key))
                ->keyedBy($key)
            );
    }
}
