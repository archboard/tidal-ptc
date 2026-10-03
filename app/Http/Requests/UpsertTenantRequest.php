<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The billing portal creates or updates a tenant by its license UUID. The `tenant:create`
 * command validates with the same rules.
 */
class UpsertTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::rulesFor($this->existingTenant());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rulesFor(?Tenant $tenant): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant)],
            'school_limit' => ['required', 'integer', 'min:1'],
            'subscription_started_at' => ['required', 'date'],
            'subscription_expires_at' => ['required', 'date', 'after:subscription_started_at'],
        ];
    }

    /**
     * A district's URL can't move under it once it's set up.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tenant = $this->existingTenant();

                if ($tenant && $this->input('domain') !== $tenant->domain && $tenant->execute(fn (Tenant $tenant) => $tenant->hasDistrictAdmin())) {
                    $validator->errors()->add('domain', 'The domain can\'t change once the district has an admin.');
                }
            },
        ];
    }

    public function existingTenant(): ?Tenant
    {
        return once(fn () => Tenant::firstWhere('license', $this->route('license')));
    }
}
