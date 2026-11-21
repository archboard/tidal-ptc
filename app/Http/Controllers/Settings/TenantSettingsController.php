<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Sis;
use App\Forms\Traits\ValidatesTenantFields;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;
use Inertia\ResponseFactory;

class TenantSettingsController extends Controller
{
    use ValidatesTenantFields;

    /**
     * Shows the tenant settings form
     *
     * @return Response|ResponseFactory
     */
    public function edit(Tenant $tenant)
    {
        $title = __('Tenant Settings');
        $schools = $tenant->schools()
            ->orderBy('name')
            ->get();

        $isClassLink = $tenant->sis_provider === Sis::CLASS_LINK;

        return inertia('settings/Tenant', [
            'title' => $title,
            'tenant' => [
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'sis_provider' => $tenant->sis_provider,
                'allow_password_auth' => $tenant->allow_password_auth,
                'allow_oidc_login' => $tenant->allow_oidc_login,
                'classlink_tenant_id' => $tenant->classlink_tenant_id,
            ],
            'classLinkConfig' => $isClassLink ? $tenant->sis_config->only(['url', 'token_url', 'client_id']) : [],
            'hasClassLinkSecret' => $isClassLink && filled($tenant->sis_config->get('client_secret')),
            'isCloud' => config('app.cloud'),
            'schoolLimit' => config('app.cloud') ? $tenant->school_limit : null,
            'smtp' => $tenant->smtp_config->toArray(),
            'sisOptions' => Sis::selectOptions(),
            'schools' => $schools->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'active' => $school->active,
            ]),
            'editable' => config('app.self_hosted'),
        ])->withViewData(compact('title'));
    }

    /**
     * Updates attributes for the tenant
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Tenant $tenant)
    {
        $isClassLink = $request->input('sis_provider') === Sis::CLASS_LINK->value;
        $keepsSecret = $isClassLink
            && $tenant->sis_provider === Sis::CLASS_LINK
            && filled($tenant->sis_config->get('client_secret'))
            && blank($request->input('sis_config.client_secret'));

        $configRules = $isClassLink ? $this->sisConfigRules(Sis::CLASS_LINK) : [];

        if ($keepsSecret) {
            $configRules['sis_config.client_secret'] = ['nullable', 'string'];
        }

        $data = $request->validate([
            ...$configRules,
            'name' => $this->nameRules(),
            ...(config('app.cloud') ? [] : ['domain' => $this->domainRules($tenant)]),
            'sis_provider' => $this->sisProviderRules(),
            'allow_password_auth' => ['boolean'],
            'allow_oidc_login' => ['boolean'],
        ]);

        $tenant->fill(Arr::except($data, 'sis_config'));

        if ($isClassLink) {
            $oldClientId = $tenant->sis_config->get('client_id');
            $config = $data['sis_config'];
            $config['client_secret'] = $keepsSecret ? $tenant->sis_config->get('client_secret') : $config['client_secret'];
            $tenant->sis_config = $config;

            // Drop the token cached for the previous credentials
            Cache::forget('classlink.token.'.$tenant->id.'.'.md5((string) $oldClientId));
            Cache::forget('classlink.token.'.$tenant->id.'.'.md5($config['client_id']));
        }

        $tenant->save();

        session()->flash('success', __('Settings updated successfully.'));

        return to_route('settings.tenant.edit');
    }
}
